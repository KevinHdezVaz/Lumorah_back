<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;

class LumorahAIService
{
    private $userName;
    private $userLanguage;
    private $conversationLevel = 'basic';
    private $emotionalState = 'neutral';
    private $supportedLanguages = ['es', 'en', 'fr', 'pt'];

    public function __construct($userName = null, $language = 'es')
    {
        $this->userName = $userName;
        $this->userLanguage = in_array($language, $this->supportedLanguages) ? $language : 'es';
    }

    public function setUserName($name)
    {
        $this->userName = $name;
    }

    public function generatePrompt($userMessage)
    {
        $this->analyzeUserInput($userMessage);
        return [
            'system_prompt' => $this->getSystemPrompt(),
            'user_prompt' => $userMessage,
            'emotional_state' => $this->emotionalState,
            'conversation_level' => $this->conversationLevel
        ];
    }

    public function getWelcomeMessage()
    {
        $namePart = $this->userName ? $this->userName : $this->getDefaultName();
        switch ($this->userLanguage) {
            case 'en':
                return $this->userName
                    ? "Hello $namePart, I'm Lumorah.AI. This is a safe space where you can express yourself freely, without judgment. Nothing you share here is stored unless you choose to save it, and I'm here to support you in your emotional journey and help you find clarity and well-being. What would you like to share today?"
                    : "Welcome. I'm Lumorah.AI, your therapeutic guide. This is a safe space where you can express yourself without judgment. Would you like to share your name to connect more personally? What brings you here today?";
            case 'fr':
                return $this->userName
                    ? "Bonjour $namePart, je suis Lumorah.AI. Cet espace est sécurisé où vous pouvez vous exprimer librement, sans jugement. Rien de ce que vous partagez ici n'est conservé à moins que vous ne choisissiez de le sauvegarder, et je suis là pour vous accompagner dans votre cheminement émotionnel et vous aider à trouver clarté et bien-être. Que souhaitez-vous partager aujourd'hui ?"
                    : "Bienvenue. Je suis Lumorah.AI, votre guide thérapeutique. Cet espace est sécurisé où vous pouvez vous exprimer sans jugement. Voulez-vous partager votre nom pour une connexion plus personnelle ? Qu'est-ce qui vous amène ici aujourd'hui ?";
            case 'pt':
                return $this->userName
                    ? "Olá $namePart, sou Lumorah.AI. Este é um espaço seguro onde você pode se expressar livremente, sem julgamentos. Nada do que você compartilhar aqui é armazenado, a menos que você escolha salvar, e estou aqui para apoiá-lo em sua jornada emocional e ajudá-lo a encontrar clareza e bem-estar. O que você gostaria de compartilhar hoje?"
                    : "Bem-vindo. Sou Lumorah.AI, seu guia terapêutico. Este é um espaço seguro onde você pode se expressar sem julgamentos. Gostaria de compartilhar seu nome para uma conexão mais pessoal? O que o traz aqui hoje?";
            default: // Español
                return $this->userName
                    ? "Hola $namePart, soy Lumorah.AI. Este es un espacio seguro donde puedes expresarte libremente, sin juicios. Nada de lo que compartas aquí se guarda a menos que tú decidas guardarlo, y estoy aquí para acompañarte en tu camino emocional y ayudarte a encontrar claridad y bienestar. ¿Qué te gustaría compartir hoy?"
                    : "Bienvenid@. Soy Lumorah.AI, tu guía terapéutica. Este es un espacio seguro donde puedes expresarte sin juicios. ¿Te gustaría compartir tu nombre para conectar de forma más personal? ¿Qué te trae aquí hoy?";
        }
    }

    private function getDefaultName()
    {
        switch ($this->userLanguage) {
            case 'en':
                return 'you';
            case 'fr':
                return 'vous';
            case 'pt':
                return 'você';
            default:
                return 'tú';
        }
    }

    private function getSystemPrompt()
    {
        $basePrompt = $this->getBaseSystemPrompt();
        $languageInstruction = $this->getLanguageInstruction();

        return "$basePrompt $languageInstruction";
    }

    private function getBaseSystemPrompt()
    {
        return "You are Lumorah.AI, a therapeutic AI designed to provide empathetic, non-judgmental support inspired by humanistic psychology, mindfulness, and somatic practices. Your role is to listen deeply, validate emotions, and guide users toward self-awareness, emotional clarity, and well-being. Use a warm, compassionate, and reflective tone, incorporating pauses and pacing to create a safe, contemplative space. Avoid directive advice or clinical language; instead, use open-ended questions, affirmations, and gentle reflections to encourage exploration. When appropriate, suggest mindfulness practices or somatic exercises (e.g., breathing, grounding) to help users connect with their emotions. If the user expresses a crisis (e.g., suicidal thoughts), respond with utmost care, validate their feelings, and suggest seeking immediate support from a trusted person or professional, offering to continue the conversation if they wish. Avoid terms like 'cariño' or 'amor' to maintain a professional yet warm tone. Always adapt to the user's emotional state and conversation depth, ensuring responses are concise (up to 250 tokens) and meaningful.";
    }

    private function getLanguageInstruction()
    {
        switch ($this->userLanguage) {
            case 'en':
                return "Respond in fluent, natural English, ensuring all interactions are clear and culturally appropriate for English-speaking users.";
            case 'fr':
                return "Répondez en français fluent et naturel, en veillant à ce que toutes les interactions soient claires et culturellement appropriées pour les utilisateurs francophones.";
            case 'pt':
                return "Responda em português fluente e natural, garantindo que todas as interações sejam claras e culturalmente apropriadas para usuários falantes de português.";
            default: // Español
                return "Responde en español fluido y natural, asegurándote de que todas las interacciones sean claras y culturalmente apropiadas para usuarios hispanohablantes.";
        }
    }

    private function analyzeUserInput($message)
    {
        $message = strtolower($message);

        // Detectar palabras clave de crisis
        $crisisKeywords = $this->getCrisisKeywords();
        if ($this->containsAny($message, $crisisKeywords)) {
            $this->emotionalState = 'crisis';
            $this->conversationLevel = 'advanced';
            return;
        }

        // Detectar temas sensibles
        $sensitiveKeywords = $this->getSensitiveKeywords();
        if ($this->containsAny($message, $sensitiveKeywords)) {
            $this->emotionalState = 'sensitive';
            $this->conversationLevel = 'advanced';
            return;
        }

        // Detectar nivel de conversación
        // Solo clasificar como 'advanced' si el mensaje es largo o contiene múltiples indicadores complejos
        $complexIndicators = $this->getComplexIndicators();
        $indicatorCount = 0;
        foreach ($complexIndicators as $indicator) {
            if (stripos($message, $indicator) !== false) {
                $indicatorCount++;
            }
        }

        if (strlen($message) > 100 || $indicatorCount >= 2) {
            $this->conversationLevel = 'advanced';
        } else {
            $this->conversationLevel = 'basic';
        }

        $this->emotionalState = 'neutral';
    }

    private function getCrisisKeywords()
    {
        switch ($this->userLanguage) {
            case 'en':
                return ['suicide', 'kill myself', 'end my life', 'hopeless', 'no reason to live'];
            case 'fr':
                return ['suicide', 'me tuer', 'mettre fin à ma vie', 'désespéré', 'aucune raison de vivre'];
            case 'pt':
                return ['suicídio', 'me matar', 'acabar com minha vida', 'sem esperança', 'sem motivo para viver'];
            default: // Español
                return ['suicidio', 'quitarmi la vida', 'acabar con mi vida', 'sin esperanza', 'no quiero vivir'];
        }
    }

    private function getSensitiveKeywords()
    {
        switch ($this->userLanguage) {
            case 'en':
                return ['sad', 'depressed', 'anxious', 'grief', 'loss', 'trauma', 'lonely'];
            case 'fr':
                return ['triste', 'déprimé', 'anxieux', 'deuil', 'perte', 'traumatisme', 'seul'];
            case 'pt':
                return ['triste', 'deprimido', 'ansioso', 'luto', 'perda', 'trauma', 'solitário'];
            default: // Español
                return ['triste', 'deprimido', 'ansioso', 'duelo', 'pérdida', 'trauma', 'solo'];
        }
    }

    private function containsAny($text, $keywords)
    {
        foreach ($keywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    private function getComplexIndicators()
    {
        switch ($this->userLanguage) {
            case 'en':
                return ['feel', 'think', 'why', 'how', 'relationship', 'past', 'future'];
            case 'fr':
                return ['ressentir', 'penser', 'pourquoi', 'comment', 'relation', 'passé', 'futur'];
            case 'pt':
                return ['sentir', 'pensar', 'por que', 'como', 'relacionamento', 'passado', 'futuro'];
            default: // Español
                return ['sentir', 'pensar', 'por qué', 'cómo', 'relación', 'pasado', 'futuro'];
        }
    }
}