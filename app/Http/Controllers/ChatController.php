<?php
namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\ChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Services\LumorahAIService;

class ChatController extends Controller
{
    protected $lumorahService;
    
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->lumorahService = new LumorahAIService(
            Auth::user()?->name,
            'es'
        );
    }

    /**
     * Obtener todas las sesiones de chat del usuario
     */
    public function getSessions()
    {
        try {
            $sessions = ChatSession::where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($session) {
                    return [
                        'id' => $session->id,
                        'user_id' => $session->user_id,
                        'title' => $session->title,
                        'is_saved' => (bool)$session->is_saved,
                        'created_at' => $session->created_at->toDateTimeString(),
                        'updated_at' => $session->updated_at->toDateTimeString(),
                        'deleted_at' => $session->deleted_at?->toDateTimeString(),
                    ];
                });
    
            return response()->json([
                'success' => true,
                'data' => $sessions,
                'count' => $sessions->count()
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al obtener sesiones: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener sesiones',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una sesión de chat
     */
    public function deleteSession($sessionId)
    {
        DB::beginTransaction();
        try {
            $session = ChatSession::where('id', $sessionId)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            Message::where('chat_session_id', $sessionId)->delete();
            $session->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Conversación eliminada exitosamente'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar sesión: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al eliminar la conversación',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guardar una conversación explícitamente
     */
    public function saveChatSession(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'messages' => 'required|array'
        ]);

        DB::beginTransaction();
        try {
            $session = ChatSession::create([
                'user_id' => Auth::id(),
                'title' => $request->title,
                'is_saved' => true
            ]);

            foreach ($request->messages as $msg) {
                Message::create([
                    'chat_session_id' => $session->id,
                    'user_id' => Auth::id(),
                    'text' => $msg['text'],
                    'is_user' => $msg['is_user'],
                    'created_at' => $msg['created_at'] ?? now(),
                ]);
            }

            DB::commit();
            return response()->json($session, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al guardar sesión: ' . $e->getMessage());
            return response()->json(['error' => 'Error al guardar el chat'], 500);
        }
    }

    /**
     * Obtener mensajes de una sesión específica
     */
    public function getSessionMessages($sessionId)
    {
        try {
            $session = ChatSession::where('id', $sessionId)
                ->where('user_id', Auth::id())
                ->firstOrFail();
                
            $messages = $session->messages()
                ->orderBy('created_at')
                ->get();
                
            return response()->json($messages, 200);
        } catch (\Exception $e) {
            Log::error('Error al obtener mensajes: ' . $e->getMessage());
            return response()->json(['error' => 'Sesión no encontrada'], 404);
        }
    }

    /**
     * Enviar mensaje y recibir respuesta (guardado en DB)
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'session_id' => 'nullable|exists:chat_sessions,id',
            'is_temporary' => 'boolean'
        ]);
        
        try {
            $promptData = $this->lumorahService->generatePrompt($request->message);
            $contextMessages = [];
            
            if ($request->session_id) {
                $contextMessages = $this->getConversationContext($request->session_id);
            }
            
            $aiResponse = $this->callOpenAI($request->message, $promptData, $contextMessages);
            
            if (!$request->is_temporary) {
                DB::transaction(function () use ($request, $aiResponse) {
                    $sessionId = $request->session_id ?? $this->createNewSession($request->message);
                    
                    Message::create([
                        'chat_session_id' => $sessionId,
                        'user_id' => Auth::id(),
                        'text' => $request->message,
                        'is_user' => true
                    ]);
                    
                    Message::create([
                        'chat_session_id' => $sessionId,
                        'user_id' => Auth::id(),
                        'text' => $aiResponse,
                        'is_user' => false
                    ]);
                });
            }
            
            return response()->json([
                'ai_message' => [
                    'text' => $aiResponse,
                    'is_user' => false,
                    'emotional_state' => $promptData['emotional_state'],
                    'conversation_level' => $promptData['conversation_level']
                ],
                'session_id' => $request->session_id ?? null
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error en sendMessage: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al procesar el mensaje',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mensaje temporal (sin guardar en DB)
     */
    public function sendTemporaryMessage(Request $request)
    {
        $request->validate(['message' => 'required|string']);
        
        $promptData = $this->lumorahService->generatePrompt($request->message);
        $aiResponse = $this->callOpenAI($request->message, $promptData);
        
        return response()->json([
            'ai_message' => [
                'text' => $aiResponse,
                'is_user' => false,
                'emotional_state' => $promptData['emotional_state'],
                'conversation_level' => $promptData['conversation_level']
            ]
        ], 200);
    }

    /**
     * Iniciar nueva conversación con Lumorah
     */
    public function startNewSession()
    {
        $welcomeMessage = $this->lumorahService->getWelcomeMessage();
        
        return response()->json([
            'ai_message' => [
                'text' => $welcomeMessage,
                'is_user' => false,
                'emotional_state' => 'neutral',
                'conversation_level' => 'basic',
                'is_welcome' => true
            ]
        ], 200);
    }

    /**
     * Actualizar nombre de usuario
     */
    public function updateUserName(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100']);
        
        Auth::user()->update(['name' => $request->name]);
        $this->lumorahService->setUserName($request->name);
        
        return response()->json([
            'ai_message' => [
                'text' => "Gracias {$request->name}. Ahora que me conoces, ¿qué te gustaría compartir hoy?",
                'is_user' => false
            ]
        ], 200);
    }

    /**
     * Guardar sesión existente
     */
    public function saveSession(Request $request, $sessionId)
    {
        $request->validate([
            'title' => 'required|string|max:100',
        ]);

        try {
            $session = ChatSession::where('id', $sessionId)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $session->update([
                'title' => $request->title,
                'is_saved' => true,
            ]);

            return response()->json($session, 200);
        } catch (\Exception $e) {
            Log::error('Error al guardar sesión: ' . $e->getMessage());
            return response()->json(['error' => 'Error al guardar la sesión'], 500);
        }
    }

    /**
     * Método callOpenAI (manteniendo nombre exacto)
     */
    private function callOpenAI($userMessage, $promptData, $context = [])
    {
        try {
            $messages = [
                ['role' => 'system', 'content' => $promptData['system_prompt']],
                ['role' => 'user', 'content' => $userMessage]
            ];
            
            foreach ($context as $msg) {
                $messages[] = [
                    'role' => $msg['is_user'] ? 'user' : 'assistant',
                    'content' => $msg['text']
                ];
            }
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini', // Manteniendo modelo exacto
                'messages' => $messages,
                'max_tokens' => 250,
                'temperature' => 0.7,
            ]);
            
            if ($response->successful()) {
                return $response->json()['choices'][0]['message']['content'];
            }
            
            Log::error('Error en OpenAI: ' . $response->body());
            return 'Lo siento, estoy teniendo dificultades. ¿Podríamos intentarlo de nuevo?';
            
        } catch (\Exception $e) {
            Log::error('Excepción en OpenAI: ' . $e->getMessage());
            return 'Estoy procesando tu mensaje. Por favor, ten paciencia...';
        }
    }
    
    protected function getConversationContext($sessionId)
    {
        return Message::where('chat_session_id', $sessionId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($msg) {
                return [
                    'text' => $msg->text,
                    'is_user' => $msg->is_user,
                    'created_at' => $msg->created_at
                ];
            })
            ->reverse()
            ->toArray();
    }
    
    protected function createNewSession($firstMessage)
    {
        $session = ChatSession::create([
            'user_id' => Auth::id(),
            'title' => $this->generateSessionTitle($firstMessage),
            'is_saved' => false
        ]);
        
        return $session->id;
    }
    
    protected function generateSessionTitle($message)
    {
        $words = str_word_count($message, 1);
        $relevantWords = array_slice($words, 0, 5);
        return 'Conversación: ' . implode(' ', $relevantWords) . '...';
    }
}