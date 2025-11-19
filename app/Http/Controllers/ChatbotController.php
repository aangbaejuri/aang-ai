<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    public function show()
    {
        $currentChatId = session('current_chat_id');
        $history = [];

        if ($currentChatId) {
            $history = session('chat_history_' . $currentChatId, []);
        }
        
        return view('chatbot', [
            'history' => $history
        ]);
    }

    public function newChat()
    {
        session()->forget('current_chat_id');
        return redirect()->route('chat.show');
    }

    public function switchChat($id)
    {
        $sessions = session('chat_sessions', []);
        
        $exists = false;
        foreach ($sessions as $session) {
            if ($session['id'] === $id) {
                $exists = true;
                break;
            }
        }

        if ($exists) {
            session(['current_chat_id' => $id]);
        }
        
        return redirect()->route('chat.show');
    }

    public function deleteChat($id)
    {
        $sessions = session('chat_sessions', []);
        
        $newSessions = array_filter($sessions, function($session) use ($id) {
            return $session['id'] !== $id;
        });
        
        session(['chat_sessions' => $newSessions]);
        session()->forget('chat_history_' . $id);

        if (session('current_chat_id') === $id) {
            session()->forget('current_chat_id');
        }
        
        return redirect()->route('chat.show');
    }

    public function clearAllChats()
    {
        $sessions = session('chat_sessions', []);
        
        foreach ($sessions as $session) {
            session()->forget('chat_history_' . $session['id']);
        }
        
        session()->forget('chat_sessions');
        session()->forget('current_chat_id');
        
        return redirect()->route('chat.show');
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $userMessage = $request->input('message');
        $apiKey = env('GOOGLE_API_KEY');

        if (!$apiKey) {
            return response()->json(['error' => 'Server AI tidak ditemukan.'], 500);
        }
        
        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}";

        $currentChatId = session('current_chat_id');
        $isNewChat = false;

        if (!$currentChatId) {
            $currentChatId = (string) Str::uuid();
            session(['current_chat_id' => $currentChatId]);
            $isNewChat = true;
        }

        $history = session('chat_history_' . $currentChatId, []);
        
        $personaInstruction = "
        Anda adalah 'Aang AI'. Seluruh identitas Anda adalah Aang AI.
        Gaya bahasa Anda gaul (gua dan lu), ekspresif, to the point, sangat natural seperti manusia, dan menyisipkan kosakata jaksel.
        Souce Code Aang AI bisa di dapatkan di https://github.com/aangbaejuri/aang-ai.git
        ";

        $primingMessages = [
            ['role' => 'user', 'parts' => [['text' => $personaInstruction]]],
            ['role' => 'model', 'parts' => [['text' => 'Tentu, saya mengerti. Saya adalah Aang AI. Saya akan menjawab semua pertanyaan berdasarkan fakta yang saya miliki.']]]
        ];

        $history[] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];

        if ($isNewChat && count($history) === 1) {
            $sessions = session('chat_sessions', []);
            $title = Str::limit($userMessage, 40);
            
            array_unshift($sessions, ['id' => $currentChatId, 'title' => $title]);
            session(['chat_sessions' => $sessions]);
        }
        
        $apiContents = array_merge($primingMessages, $history);

        try {
            $response = Http::post($apiUrl, [
                'contents' => $apiContents
            ]);
            
            if (!$response->successful()) {
                Log::error('Server Error: ', $response->json());
                return response()->json(['error' => 'Gagal terhubung ke Server.'], $response->status());
            }
            
            $aiResponse = $response->json();
            
            if (isset($aiResponse['candidates'][0]['content']['parts'][0]['text'])) {
                
                $replyText = $aiResponse['candidates'][0]['content']['parts'][0]['text'];
                
                $history[] = ['role' => 'model', 'parts' => [['text' => $replyText]]];
                session(['chat_history_' . $currentChatId => $history]);

                $newChatInfo = $isNewChat ? ['id' => $currentChatId, 'title' => $title] : null;

                return response()->json(['reply' => $replyText, 'newChatInfo' => $newChatInfo]);

            } else {
                Log::error('Server - No valid reply structure: ', $aiResponse);
                
                $errorMessage = 'Model AI tidak memberikan respon yang valid.';
                if(isset($aiResponse['promptFeedback']['blockReason'])) {
                    $errorMessage = 'Respon diblokir: ' . $aiResponse['promptFeedback']['blockReason'];
                }

                if (isset($history[count($history) - 1]['role']) && $history[count($history) - 1]['role'] === 'user') {
                    array_pop($history);
                    session(['chat_history_' . $currentChatId => $history]);
                }
                
                return response()->json(['error' => $errorMessage], 500);
            }

        } catch (\Exception $e) {
            Log::error('Exception Error: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan internal: ' . $e->getMessage()], 500);
        }
    }
}
