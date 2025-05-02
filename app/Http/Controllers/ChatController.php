<?php
namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Obtener todas las sesiones de chat del usuario autenticado
     */
    public function getSessions()
    {
        try {
            $sessions = ChatSession::where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->get();
            return response()->json($sessions, 200);
        } catch (\Exception $e) {
            Log::error('Error al obtener sesiones: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener sesiones'], 500);
        }
    }

    /**
     * Obtener los mensajes de una sesión específica
     */
    public function getSessionMessages($sessionId)
    {
        try {
            $session = ChatSession::where('id', $sessionId)
                ->where('user_id', Auth::id())
                ->firstOrFail();
            $messages = $session->messages()->orderBy('created_at')->get();
            return response()->json($messages, 200);
        } catch (\Exception $e) {
            Log::error('Error al obtener mensajes: ' . $e->getMessage());
            return response()->json(['error' => 'Sesión no encontrada'], 404);
        }
    }

    /**
     * Enviar un mensaje y obtener respuesta de la IA
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'session_id' => 'nullable|exists:chat_sessions,id',
            'message' => 'required|string',
        ]);

        try {
            $user = Auth::user();
            $session = null;

            // Crear o usar una sesión existente
            if ($request->session_id) {
                $session = ChatSession::where('id', $request->session_id)
                    ->where('user_id', $user->id)
                    ->firstOrFail();
            } else {
                $session = ChatSession::create([
                    'user_id' => $user->id,
                    'title' => substr($request->message, 0, 50),
                ]);
            }

            // Guardar mensaje del usuario
            $userMessage = Message::create([
                'chat_session_id' => $session->id,
                'user_id' => $user->id,
                'text' => $request->message,
                'is_user' => true,
            ]);

            // Obtener respuesta de OpenAI
            $aiResponse = $this->callOpenAI($request->message, $session);

            // Guardar respuesta de la IA
            $aiMessage = Message::create([
                'chat_session_id' => $session->id,
                'user_id' => $user->id,
                'text' => $aiResponse,
                'is_user' => false,
            ]);

            return response()->json([
                'session' => $session,
                'user_message' => $userMessage,
                'ai_message' => $aiMessage,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al enviar mensaje: ' . $e->getMessage());
            return response()->json(['error' => 'Error al procesar el mensaje'], 500);
        }
    }

    /**
     * Llamar a la API de OpenAI
     */
    private function callOpenAI($message, $session)
    {
        try {
            // Obtener mensajes anteriores para contexto
            $previousMessages = $session->messages()->orderBy('created_at')->get()->map(function ($msg) {
                return [
                    'role' => $msg->is_user ? 'user' : 'assistant',
                    'content' => $msg->text,
                ];
            })->toArray();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'Eres un asistente útil que responde en español.'],
                    ...$previousMessages,
                    ['role' => 'user', 'content' => $message],
                ],
                'max_tokens' => 150,
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                return $response->json()['choices'][0]['message']['content'];
            }

            Log::error('Error en la API de OpenAI: ' . $response->body());
            return 'Lo siento, no pude procesar tu solicitud.';
        } catch (\Exception $e) {
            Log::error('Excepción en la API de OpenAI: ' . $e->getMessage());
            return 'Lo siento, ocurrió un error.';
        }
    }
}