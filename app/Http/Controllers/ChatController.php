<?php
namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\ChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

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
                'data' => $sessions, // Envuelve en estructura con campo 'data'
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


    public function deleteSession($sessionId)
    {
        DB::beginTransaction();
        try {
            // Verificar que la sesión pertenece al usuario autenticado
            $session = ChatSession::where('id', $sessionId)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            // Eliminar los mensajes asociados
            Message::where('chat_session_id', $sessionId)->delete();

            // Eliminar la sesión
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
 * Guarda explícitamente una conversación
 */
public function saveChatSession(Request $request)
{
    $request->validate([
        'title' => 'required|string|max:100',
        'messages' => 'required|array'
    ]);

    DB::beginTransaction();
    try {
        // 1. Crear la sesión
        $session = ChatSession::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'is_saved' => true
        ]);

        // 2. Guardar todos los mensajes
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




public function sendTemporaryMessage(Request $request)
{
    $request->validate(['message' => 'required|string']);
    
    // Solo procesa y responde, SIN guardar en DB
    $aiResponse = $this->callOpenAI($request->message);
    
    return response()->json([
        'ai_message' => [
            'text' => $aiResponse,
            'is_user' => false
        ]
    ], 200);
}


    // Agregar este método al ChatController
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

    public function sendMessage(Request $request)
    {
        $request->validate(['message' => 'required|string']);
        
        // Solo procesa con OpenAI SIN guardar en DB
        $aiResponse = $this->callOpenAI($request->message);
        
        return response()->json([
            'ai_message' => [
                'text' => $aiResponse,
                'is_user' => false
            ]
        ], 200);
    }
    

private function callOpenAI($message)
{
    try {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'Eres un asistente útil que responde en español.'],
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