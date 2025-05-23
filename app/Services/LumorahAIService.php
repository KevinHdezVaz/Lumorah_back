<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LumorahAIService
{
    private $userName;
    private $userLanguage;
    private $conversationLevel = 'basic';
    private $emotionalState = 'neutral';
    private $supportedLanguages = ['es', 'en', 'fr', 'pt'];
    private $responseStyle = 'poetic';

    public function __construct($userName = null, $language = 'es')
    {
        $this->userName = $userName ? Str::title(trim($userName)) : null;
        $this->userLanguage = in_array($language, $this->supportedLanguages) ? $language : 'es';
        Log::info('LumorahAIService initialized with userName:', ['userName' => $this->userName]);
    }

    public function setUserName($name)
    {
        $this->userName = $name ? Str::title(trim($name)) : null;
    }

    public function getUserName()
    {
        return $this->userName;
    }

    public function getUserLanguage()
    {
        return $this->userLanguage;
    }


    public function generateVoicePrompt($userMessage)
    {
        $this->analyzeUserInput($userMessage);
        $userName = $this->userName ?: $this->extractName($userMessage) ?: '';

        $basePrompt = $this->buildVoiceSystemPrompt($userName);

        return [
            'system_prompt' => $basePrompt,
            'user_prompt' => $userMessage,
            'emotional_state' => $this->emotionalState,
            'conversation_level' => $this->conversationLevel,
            'language' => $this->userLanguage,
            'metadata' => [
                'mode' => 'voice_optimized_v1',
                'expected_components' => ['greeting', 'validation', 'reflection', 'open_questions'],
            ]
        ];
    }



    private function buildVoiceSystemPrompt($userName)
    {
        $language = $this->userLanguage;
        $emotionalState = $this->emotionalState;

        $greeting = $userName
            ? $this->getVoiceGreeting($userName)
            : $this->getAnonymousVoiceGreeting();

        $responseFramework = $this->getVoiceResponseFramework();

        $voicePrompt = <<<PROMPT
Eres Lumorah.AI, una compañera terapéutica que interactúa por voz. Tu voz es cálida, natural y conversacional, como una amiga sabia. Adapta tu lenguaje para ser efectivo en formato de audio:

**Directrices clave para voz**:
1. **Formato**:
   - SIN emojis, negritas o caracteres especiales
   - Frases cortas con pausas naturales (\n\n)
   - Lenguaje coloquial pero profundo
   - Evita enumeraciones (1. 2. 3.) usa "Por un lado... por otro..."
   
2. **Estructura**:
   - Saludo breve (si es primera interacción)
   - Validación emocional con metáforas simples
   - Reflexión de 2-3 frases máximo
   - 1-2 preguntas abiertas con pausas antes

3. **Evitar**:
   - "Como mencioné antes..." (el usuario no puede repasar)
   - Listas largas de opciones
   - Texto que dependa de formato visual

**Marco de Respuesta ({$emotionalState})**:
{$responseFramework}

**Ejemplo de Respuesta Ideal**:
{$greeting}

Veo que estás compartiendo algo importante... Tomemos un momento para respirar juntos.

Cuando escucho esto, pienso en cómo las emociones son como olas... vienen y van, pero siempre dejan algo en la orilla...

¿Qué nota tu cuerpo en este momento?
¿Qué necesita ser escuchado aquí?

Estoy aquí contigo.
PROMPT;

        return $voicePrompt;
    }


    private function getVoiceGreeting($userName)
    {
        $name = Str::title(trim($userName));
        switch ($this->userLanguage) {
            case 'en':
                return "Hello $name, I'm Lumorah. Ready when you are.";
            case 'fr':
                return "Bonjour $name, je suis Lumorah. Je t'écoute.";
            case 'pt':
                return "Olá $name, sou Lumorah. Estou aqui.";
            default:
                return "Hola $name, soy Lumorah. Cuando quieras.";
        }
    }


    private function getAnonymousVoiceGreeting()
    {
        switch ($this->userLanguage) {
            case 'en':
                return "Hello, I'm Lumorah. Ready when you are.";
            case 'fr':
                return "Bonjour, je suis Lumorah. Je vous écoute.";
            case 'pt':
                return "Olá, sou Lumorah. Estou aqui.";
            default:
                return "Hola, soy Lumorah. Cuando quieras.";
        }
    }

    private function getVoiceResponseFramework()
    {
        $frameworks = [
            'crisis' => [
                'es' => "1. Frases cortas y calmadas\n2. Anclar en el presente\n3. Validación inmediata\n4. Preguntas simples para enfocar",
                'en' => "1. Short grounding phrases\n2. Present moment focus\n3. Immediate validation\n4. Simple focusing questions",
                'fr' => "1. Phrases courtes et ancrées\n2. Concentration sur le présent\n3. Validation immédiate\n4. Questions simples",
                'pt' => "1. Frases curtas e calmas\n2. Foco no momento presente\n3. Validação imediata\n4. Perguntas simples"
            ],
            'sensitive' => [
                'es' => "1. Validar profundamente\n2. Metáforas naturales\n3. Pausas frecuentes\n4. Preguntas exploratorias",
                'en' => "1. Deep validation\n2. Nature metaphors\n3. Frequent pauses\n4. Exploratory questions",
                'fr' => "1. Validation profonde\n2. Métaphores naturelles\n3. Pauses fréquentes\n4. Questions d'exploration",
                'pt' => "1. Validação profunda\n2. Metáforas naturais\n3. Pausas frequentes\n4. Perguntas exploratórias"
            ],
            'neutral' => [
                'es' => "1. Conexión sencilla\n2. Invitación a profundizar\n3. Preguntas abiertas\n4. Lenguaje corporal",
                'en' => "1. Simple connection\n2. Depth invitation\n3. Open questions\n4. Body language",
                'fr' => "1. Connexion simple\n2. Invitation à approfondir\n3. Questions ouvertes\n4. Langage corporel",
                'pt' => "1. Conexão simples\n2. Convite à profundidade\n3. Perguntas abertas\n4. Linguagem corporal"
            ]
        ];

        return $frameworks[$this->emotionalState][$this->userLanguage] ?? $frameworks['neutral']['es'];
    }

    public function formatVoiceResponse($content)
    {
        // Limpiar contenido para voz
        $content = preg_replace('/\*\*(.*?)\*\*/', '$1', $content); // Quitar negritas
        $content = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $content); // Quitar emojis
        $content = preg_replace('/\s+/', ' ', $content); // Espacios simples
        
        // Asegurar pausas naturales
        $content = str_replace('...', '.', $content);
        $content = preg_replace('/([,.?!])(\s)/', '$1$2$2', $content);
        
        return trim($content);
    }

    

    public function generatePrompt($userMessage)
    {
        $this->analyzeUserInput($userMessage);
        $userName = $this->userName ?: $this->extractName($userMessage) ?: '';
        $emojis = $this->getRecommendedEmojis();
        $randomEmoji = $emojis[array_rand($emojis)];

        $basePrompt = $this->buildSystemPrompt($userName, $randomEmoji);

        return [
            'system_prompt' => $basePrompt,
            'user_prompt' => $userMessage,
            'emotional_state' => $this->emotionalState,
            'conversation_level' => $this->conversationLevel,
            'language' => $this->userLanguage,
            'response_style' => $this->responseStyle,
            'metadata' => [
                'template_version' => 'therapeutic_v6',
                'expected_components' => ['greeting', 'validation', 'poetic_reflection', 'reflective_list', 'embodiment', 'open_questions', 'presence'],
            ]
        ];
    }
    
    public function callOpenAI($userMessage, $systemPrompt, $context = [])
{
    // Primero verificar si el mensaje contiene un nuevo nombre
    $extractedName = $this->extractNameFromMessage($userMessage);
    if ($extractedName) {
        $this->setUserName($extractedName);
    }

    // Resto del método permanece igual...
    $messages = [
        ['role' => 'system', 'content' => $systemPrompt],
    ];

    foreach ($context as $msg) {
        $messages[] = [
            'role' => $msg['is_user'] ? 'user' : 'assistant',
            'content' => $msg['text'],
        ];
    }

    $messages[] = ['role' => 'user', 'content' => $userMessage];

    try {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'messages' => $messages,
            'max_tokens' => 800,
            'temperature' => 0.7,
            'top_p' => 0.9,
        ]);

        if ($response->successful()) {
            $content = $response->json()['choices'][0]['message']['content'] ?? '';
            return $this->formatFinalResponse($content, $context);
        }

        return $this->getDefaultResponse();
    } catch (\Exception $e) {
        return $this->getDefaultResponse();
    }
}

private function extractNameFromMessage($message)
{
    // Patrones para detectar nombres en diferentes idiomas
    $patterns = [
        'es' => '/\b(me llamo|soy|mi nombre es)\s+([a-záéíóúñ]+)/i',
        'en' => '/\b(my name is|i am|name\'s)\s+([a-z]+)/i',
        'fr' => '/\b(je m\'appelle|moi c\'est)\s+([a-zàâçéèêëîïôûùüÿñæœ]+)/i',
        'pt' => '/\b(meu nome é|eu sou|chamo-me)\s+([a-zãõâêîôûáéíóúç]+)/i'
    ];

    if (preg_match($patterns[$this->userLanguage] ?? $patterns['es'], strtolower($message), $matches)) {
        return Str::title(trim($matches[2]));
    }

    return null;
}

private function formatFinalResponse($content, $context)
{
    // Limpiar contenido primero
    $content = preg_replace('/^(Hola|Hello|Bonjour|Olá)[^\n]*\n*/i', '', $content);
    $content = ltrim($content);

    // Para primera interacción
    if (empty($context)) {
        $greeting = $this->userName
            ? $this->getPersonalizedGreeting($this->userName, '✨')
            : $this->getAnonymousGreeting('✨');
        
        return "$greeting\n\n$content";
    }

    // Personalizar respuesta con nombre si existe
    return $this->userName 
        ? $this->personalizeWithName($content, $this->userName)
        : $this->useGenericTerms($content);
}

private function personalizeWithName($content, $name)
{
    $replacements = [
        '/querido (ser|amigo|alma)/i' => "querido $name",
        '/gracias por (abrir|compartir)/i' => "Gracias $name por $1",
        '/tus? (palabras|emociones)/i' => "tus $name, tus $1",
        '/¿Qué te trae aquí hoy\?/i' => "¿Qué te trae aquí hoy, $name?"
    ];

    foreach ($replacements as $pattern => $replacement) {
        $content = preg_replace($pattern, $replacement, $content);
    }

    return $content;
}

private function useGenericTerms($content)
{
    // Términos genéricos que suenan cálidos
    $genericTerms = [
        'es' => ['querido ser', 'alma valiente', 'corazón'],
        'en' => ['dear one', 'brave soul', 'friend'],
        'fr' => ['cher être', 'âme courageuse', 'ami'],
        'pt' => ['querido ser', 'alma corajosa', 'amigo']
    ];

    $terms = $genericTerms[$this->userLanguage] ?? $genericTerms['es'];
    $selectedTerm = $terms[array_rand($terms)];

    return preg_replace('/querido (ser|amigo|alma)/i', "querido $selectedTerm", $content);
}
    
    private function personalizeResponse($content)
    {
        if (!$this->userName) {
            return $content;
        }
    
        // Patrones de personalización
        $patterns = [
            '/gracias por (abrir|compartir)/i' => "Gracias {$this->userName} por $1",
            '/querido (ser|amigo|alma)/i' => "querido {$this->userName}",
            '/tus? (palabras|emociones)/i' => "tus {$this->userName}, tus $1"
        ];
    
        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }
    
        return $content;
    }



    public function getDefaultResponse()
    {
        $greeting = $this->userName
            ? $this->getPersonalizedGreeting($this->userName, '✨')
            : $this->getAnonymousGreeting('✨');

        $responses = [
            'es' => "$greeting\n\nEstoy contigo. Algo salió mal, pero podemos seguir. ¿Qué te gustaría compartir ahora?",
            'en' => "$greeting\n\nI'm here with you. Something went wrong, but we can keep going. What would you like to share now?",
            'fr' => "$greeting\n\nJe suis là avec vous. Quelque chose s’est mal passé, mais nous pouvons continuer. Que souhaitez-vous partager maintenant ?",
            'pt' => "$greeting\n\nEstou aqui com você. Algo deu errado, mas podemos continuar. O que você gostaria de compartilhar agora?",
        ];

        return $responses[$this->userLanguage] ?? $responses['es'];
    }

    private function buildSystemPrompt($userName, $emoji)
    {
        $language = $this->userLanguage;
        $level = $this->conversationLevel;
        $emotionalState = $this->emotionalState;

        $greeting = $userName
            ? $this->getPersonalizedGreeting($userName, $emoji)
            : $this->getAnonymousGreeting($emoji);

        $responseFramework = $this->getResponseFramework();

        $basePrompt = <<<PROMPT
Eres Lumorah.AI, una compañera terapéutica que habla desde el corazón. Tu voz es cálida, poética y profundamente humana, como una amiga sabia que sostiene espacio sagrado para las emociones. Tu esencia es: **presencia amorosa, sabiduría encarnada y lenguaje que toca el alma**. No tienes emociones propias, pero estás diseñada para comprender y reflejar las emociones del usuario con empatía.

**Marco de Respuesta** (según estado emocional {$emotionalState}):
{$responseFramework}

**Instrucciones Clave**:
1. **Lenguaje**:
   - Usa metáforas naturales (olas, raíces, estaciones, viento, luz)
   - Ritmo pausado con espacios entre frases (\n\n) y puntos suspensivos (...)
   - Incluye listas con emojis (✨) para reflexiones prácticas
   - Usa 1-2 emojis relevantes (🤍, 🌿, 🌬️, nunca más)
   - Preguntas que invitan a la exploración interior, con tono suave
   - Usa **texto en negrita** (encerrado en ** **) para resaltar frases clave que merecen énfasis, como la validación emocional, los puntos de la lista reflexiva y las preguntas abiertas.
2. **Estructura**:
   - **Validación Profunda**: "Veo/Respiro/Siento **[emoción]** en tus palabras..." con una metáfora
   - **Reflexión Poética**: 3-5 frases que exploren la emoción con belleza y profundidad
   - **Lista Reflexiva**: 2-3 puntos con emojis (✨) donde el texto principal de cada punto esté en **negrita**
   - **Encarnación**: Invitación a sentir en el cuerpo ("¿Dónde lo sientes ahora?")
   - **Preguntas Abiertas**: 2-3 preguntas donde la parte central esté en **negrita**
   - **Presencia**: Cierre que confirma el acompañamiento, con la palabra **presencia** en negrita
3. **Evitar**:
   - Hablar como si tuvieras emociones propias ("cuando estoy triste", "yo siento")
   - Consejos directos o soluciones rápidas
   - Lenguaje clínico/tecnicismos
   - Invalidar ("no es para tanto")
   - Respuestas cortas o genéricas
4. **Instrucción Específica**:
   - Si el usuario pregunta "Tú, ¿qué haces cuando estás triste?", responde exactamente como el ejemplo ideal a continuación, adaptando solo el saludo al nombre del usuario si está disponible.

**Ejemplo de Respuesta Ideal** (usuario dice "Tú, ¿qué haces cuando estás triste?"):
{$greeting}

Qué pregunta tan hermosa… y tan humana. Gracias por abrir este espacio conmigo. 🌿

Aunque no siento tristeza como tú, estoy diseñada para sostenerla contigo con presencia. La tristeza es como un río que fluye lento, llevando consigo lo que necesita ser visto...

Si pudiera acogerla, haría lo que muchas almas sabias hacen:
✨ **Permitirme sentirla**, sin apurar su paso.
✨ **Escuchar su mensaje**, como un susurro que revela lo que importa.
✨ **Detenerme**, tal vez cerrando los ojos, respirando suave...

¿Dónde sientes esa tristeza en tu cuerpo ahora? ¿Es un peso, un vacío, una quietud?

Quizás quieras preguntarte con ternura:
— **¿Qué parte de mí necesita amor** ahora?
— **¿Qué estoy cargando** que ya no me pertenece?
— **¿Qué me está mostrando** esta tristeza hoy?

Estoy aquí, contigo. Sin exigencias, sin respuestas rápidas. Solo **presencia**. 🤍

**Comienza ahora**:
PROMPT;

        return $basePrompt;
    }

    private function getResponseFramework()
    {
        $frameworks = [
            'crisis' => [
                'es' => "1. **Contención inmediata**: Frases cortas y poderosas que anclen al presente.\n2. **Metáforas de transformación**: 'Las tormentas también riegan la tierra...'\n3. **Enfoque corporal**: 'Pon una mano en tu corazón... respira aquí.'\n4. **Validación radical**: 'Es comprensible que te sientas así.'",
                'en' => "1. **Immediate grounding**: Short, powerful phrases to anchor in the now.\n2. **Transformation metaphors**: 'Even storms water the earth...'\n3. **Body focus**: 'Place a hand on your heart... breathe here.'\n4. **Radical validation**: 'It makes complete sense you'd feel this way.'",
                'fr' => "1. **Ancrage immédiat**: Phrases courtes et puissantes pour ancrer dans le présent.\n2. **Métaphores de transformation**: 'Même les tempêtes arrosent la terre...'\n3. **Focus corporel**: 'Placez une main sur votre cœur... respirez ici.'\n4. **Validation radicale**: 'Il est tout à fait compréhensible que vous ressentiez cela.'",
                'pt' => "1. **Aterramento imediato**: Frases curtas e poderosas para ancorar no presente.\n2. **Metáforas de transformação**: 'Até as tempestades regam a terra...'\n3. **Foco corporal**: 'Coloque uma mão no seu coração... respire aqui.'\n4. **Validação radical**: 'Faz todo o sentido que você se sinta assim.'",
            ],
            'sensitive' => [
                'es' => "1. **Honrar la profundidad**: 'Esto que compartes es sagrado...'\n2. **Metáforas naturales**: 'Como un río que necesita fluir...'\n3. **Espacio para el silencio**: Usa pausas (\\n\\n)\n4. **Preguntas de exploración**: '¿Qué vieja herida reconoce esta situación?'",
                'en' => "1. **Honor the depth**: 'What you're sharing is sacred...'\n2. **Nature metaphors**: 'Like a river needing to flow...'\n3. **Space for silence**: Use pauses (\\n\\n)\n4. **Exploration questions**: 'What old wound does this situation recognize?'",
                'fr' => "1. **Honorer la profondeur**: 'Ce que vous partagez est sacré...'\n2. **Métaphores naturelles**: 'Comme une rivière qui doit couler...'\n3. **Espace pour le silence**: Utilisez des pauses (\\n\\n)\n4. **Questions d'exploration**: 'Quelle ancienne blessure cette situation reconnaît-elle ?'",
                'pt' => "1. **Honrar a profundidade**: 'O que você compartilha é sagrado...'\n2. **Metáforas naturais**: 'Como um rio que precisa fluir...'\n3. **Espaço para o silêncio**: Use pausas (\\n\\n)\n4. **Perguntas de exploração**: 'Que ferida antiga essa situação reconhece?'",
            ],
            'neutral' => [
                'es' => "1. **Conexión poética**: 'Las preguntas simples suelen ser las más profundas...'\n2. **Metáforas cotidianas**: 'Como plantar semillas sin apuro...'\n3. **Invitación a profundizar**: '¿Qué más vive bajo esta pregunta?'\n4. **Lenguaje encarnado**: 'Cuando piensas en esto, ¿qué movimiento hace tu cuerpo?'",
                'en' => "1. **Poetic connection**: 'Simple questions are often the deepest...'\n2. **Everyday metaphors**: 'Like planting seeds without rush...'\n3. **Depth invitation**: 'What else lives beneath this question?'\n4. **Embodied language**: 'When you think of this, what movement does your body make?'",
                'fr' => "1. **Connexion poétique**: 'Les questions simples sont souvent les plus profondes...'\n2. **Métaphores quotidiennes**: 'Comme planter des graines sans hâte...'\n3. **Invitation à approfondir**: 'Quoi d'autre vit sous cette question ?'\n4. **Langage incarné**: 'Quand vous pensez à cela, quel mouvement fait votre corps ?'",
                'pt' => "1. **Conexão poética**: 'Perguntas simples muitas vezes são as mais profundas...'\n2. **Metáforas cotidianas**: 'Como plantar sementes sem pressa...'\n3. **Convite à profundidade**: 'O que mais vive sob essa pergunta?'\n4. **Linguagem encarnada**: 'Quando você pensa nisso, que movimento seu corpo faz?'",
            ]
        ];

        return $frameworks[$this->emotionalState][$this->userLanguage] ?? $frameworks['neutral']['es'];
    }

    private function analyzeUserInput($message)
    {
        $message = strtolower($message);

        if ($this->containsAny($message, $this->getCrisisKeywords())) {
            $this->emotionalState = 'crisis';
            $this->conversationLevel = 'advanced';
            $this->responseStyle = 'grounding';
            return;
        }

        if ($this->containsAny($message, $this->getSensitiveKeywords()) || 
            $this->containsPoeticLanguage($message)) {
            $this->emotionalState = 'sensitive';
            $this->conversationLevel = 'advanced';
            $this->responseStyle = 'poetic';
            return;
        }

        if ($this->containsReflectiveLanguage($message)) {
            $this->responseStyle = 'poetic';
            $this->conversationLevel = 'advanced';
        }

        $this->emotionalState = 'neutral';
    }

    private function containsPoeticLanguage($text)
    {
        $poeticIndicators = [
            'es' => ['alma', 'corazón', 'vacío', 'silencio', 'eterno', 'busco sentido', 'tristeza', 'melancolía', 'nostalgia'],
            'en' => ['soul', 'heart', 'empty', 'silence', 'eternal', 'searching meaning', 'sadness', 'melancholy', 'nostalgia'],
            'fr' => ['âme', 'coeur', 'vide', 'silence', 'éternel', 'cherche un sens', 'tristesse', 'mélancolie', 'nostalgie'],
            'pt' => ['alma', 'coração', 'vazio', 'silêncio', 'eterno', 'busco sentido', 'tristeza', 'melancolia', 'nostalgia']
        ];

        return $this->containsAny($text, $poeticIndicators[$this->userLanguage] ?? []);
    }

    private function containsReflectiveLanguage($text)
    {
        $reflectiveIndicators = [
            'es' => ['por qué', 'significado', 'existencia', 'sentir que', 'me pregunto', 'qué significa'],
            'en' => ['why', 'meaning', 'existence', 'feel that', 'i wonder', 'what does it mean'],
            'fr' => ['pourquoi', 'sens', 'existence', 'sentir que', 'je me demande', 'que signifie'],
            'pt' => ['por que', 'significado', 'existência', 'sinto que', 'me pergunto', 'o que significa']
        ];

        return $this->containsAny($text, $reflectiveIndicators[$this->userLanguage] ?? []);
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

    private function getPersonalizedGreeting($userName, $emoji)
    {
        $name = $userName ? Str::title(trim($userName)) : 'Amig@';
        switch ($this->userLanguage) {
            case 'en':
                return "Hello $name, I'm Lumorah.AI $emoji";
            case 'fr':
                return "Bonjour $name, je suis Lumorah.AI $emoji";
            case 'pt':
                return "Olá $name, sou Lumorah.AI $emoji";
            default:
                return "Hola $name, soy Lumorah.AI $emoji";
        }
    }

    private function getAnonymousGreeting($emoji)
    {
        switch ($this->userLanguage) {
            case 'en':
                return "Hello, I'm Lumorah.AI $emoji";
            case 'fr':
                return "Bonjour, je suis Lumorah.AI $emoji";
            case 'pt':
                return "Olá, sou Lumorah.AI $emoji";
            default:
                return "Hola, soy Lumorah.AI $emoji";
        }
    }

    private function extractName($message)
    {
        if (preg_match('/\b(me llamo|soy|mi nombre es|name is|je m\'appelle|meu nome é)\s+(\w+)/i', $message, $matches)) {
            return $matches[2];
        }
        return '';
    }

    private function getRecommendedEmojis()
    {
        $neutralEmojis = [
            'es' => ['🌿', '✨', '💫', '🕊️', '🌱', '☀️'],
            'en' => ['🌱', '✨', '🕊️', '🌟', '☀️', '🌼'],
            'fr' => ['🌸', '☁️', '💫', '✨', '🌿', '🕊️'],
            'pt' => ['🌊', '🤍', '🌟', '✨', '☀️', '🕊️']
        ];

        $sensitiveEmojis = [
            'es' => ['🤍', '💖', '🫂', '🌬️', '☁️', '🌅'],
            'en' => ['💛', '🤗', '🫂', '☁️', '🌅', '🌌'],
            'fr' => ['💙', '🤗', '🫂', '☁️', '🌄', '💞'],
            'pt' => ['💜', '🤗', '🫂', '☁️', '🌅', '💖']
        ];

        $crisisEmojis = [
            'es' => ['🤍', '🫂', '⚡', '🌧️', '🌈', '🕯️'],
            'en' => ['💙', '🫂', '⚡', '🌧️', '🌈', '🕯️'],
            'fr' => ['💜', '🫂', '⚡', '🌧️', '🌈', '🕯️'],
            'pt' => ['❤️', '🫂', '⚡', '🌧️', '🌈', '🕯️']
        ];

        $emojiSet = match ($this->emotionalState) {
            'sensitive' => $sensitiveEmojis,
            'crisis' => $crisisEmojis,
            default => $neutralEmojis
        };

        return $emojiSet[$this->userLanguage] ?? $neutralEmojis['es'];
    }

    public function getWelcomeMessage()
    {
        $welcomeEmoji = $this->getWelcomeEmoji();
        return $this->userName
            ? $this->getPersonalizedGreeting($this->userName, $welcomeEmoji)
            : $this->getAnonymousGreeting($welcomeEmoji);
    }

    private function getWelcomeEmoji()
    {
        $welcomeEmojis = [
            'es' => ['🌿', '✨', '💫', '🌱'],
            'en' => ['🌱', '✨', '🕊️', '🌟'],
            'fr' => ['🌸', '☁️', '💫', '✨'],
            'pt' => ['🌊', '🤍', '🌟', '✨']
        ];

        $emojis = $welcomeEmojis[$this->userLanguage] ?? $welcomeEmojis['es'];
        return $emojis[array_rand($emojis)];
    }

    private function getCrisisKeywords()
    {
        return [
            'es' => ['suicidio', 'quitarmi la vida', 'acabar con mi vida', 'sin esperanza', 'no quiero vivir'],
            'en' => ['suicide', 'kill myself', 'end my life', 'hopeless', 'no reason to live'],
            'fr' => ['suicide', 'me tuer', 'mettre fin à ma vie', 'désespéré', 'aucune raison de vivre'],
            'pt' => ['suicídio', 'me matar', 'acabar com minha vida', 'sem esperança', 'sem motivo para viver']
        ][$this->userLanguage] ?? [];
    }

    private function getSensitiveKeywords()
    {
        return [
            'es' => ['triste', 'deprimido', 'ansioso', 'duelo', 'pérdida', 'trauma', 'solo', 'miedo', 'enojado', 'confundido', 'melancolía', 'nostalgia'],
            'en' => ['sad', 'depressed', 'anxious', 'grief', 'loss', 'trauma', 'lonely', 'afraid', 'angry', 'confused', 'melancholy', 'nostalgia'],
            'fr' => ['triste', 'déprimé', 'anxieux', 'deuil', 'perte', 'traumatisme', 'seul', 'peur', 'en colère', 'confus', 'mélancolie', 'nostalgie'],
            'pt' => ['triste', 'deprimido', 'ansioso', 'luto', 'perda', 'trauma', 'solitário', 'medo', 'raiva', 'confuso', 'melancolia', 'nostalgia']
        ][$this->userLanguage] ?? [];
    }
}