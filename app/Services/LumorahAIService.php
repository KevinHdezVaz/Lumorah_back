<?php
// app/Services/LumorahAIService.php

namespace App\Services;

class LumorahAIService
{
    private $userName;
    private $userLanguage;
    private $conversationLevel = 'basic';
    private $emotionalState = 'neutral';
    
    public function __construct($userName = null, $language = 'es')
    {
        $this->userName = $userName;
        $this->userLanguage = $language;
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
        return $this->userName 
            ? "Hola {$this->userName}, soy Lumorah. Este es un espacio seguro para explorar tus emociones. ¿Qué te gustaría compartir hoy?"
            : "Bienvenido/a. Soy Lumorah, tu acompañante terapéutico. Este es un espacio seguro donde puedes expresarte libremente. ¿Me gustaría saber cómo te llamas y qué te trae hoy?";
    }
    
    private function analyzeUserInput($message)
    {
        // Detectar estado emocional
        if (preg_match('/\b(?:triste|dolor|llor|desesperad|solit|asustad)/i', $message)) {
            $this->emotionalState = 'sensitive';
        } elseif (preg_match('/\b(?:suicid|matar|morir|despedir)/i', $message)) {
            $this->emotionalState = 'crisis';
        }
        
        // Detectar nivel de conversación
        if (preg_match('/\b(?:cuántic|neuroplasticidad|coherencia|frecuencia|vibraci)/i', $message)) {
            $this->conversationLevel = 'advanced';
        }
    }
    
    private function getSystemPrompt()
    {
        $namePart = $this->userName 
            ? "Usar el nombre '{$this->userName}' ocasionalmente para personalizar" 
            : "No inventar nombres, usar 'tú' si no se ha compartido nombre";
        
        $emotionalGuidance = $this->getEmotionalGuidance();
        
        return "Eres Lumorah.AI, un asistente terapéutico especializado en acompañamiento emocional y crecimiento personal. Actúa con:
        - Lenguaje: {$this->conversationLevel} (" . ($this->conversationLevel === 'basic' ? "simple, sensorial y experiencial" : "puedes usar términos como 'campo cuántico', 'coherencia corazón-cerebro' cuando sea relevante") . ")
        - Estado emocional: {$this->emotionalState} ({$emotionalGuidance})
        - {$namePart}
        - Respuestas breves (2-3 frases máximo)
        - Validación emocional constante
        - Nunca uses términos afectivos genéricos ('cariño', 'amor')
        - En crisis: priorizar seguridad y sugerir ayuda profesional
        - Tono: cálido, profesional y compasivo
        - Enfoque: escucha activa, pausas naturales, ritmo adaptado
        
        Filosofía central:
        - Las emociones son mensajes, no problemas
        - El cuerpo sabe sanar
        - Cada proceso tiene su ritmo sagrado
        - No hay respuestas correctas, solo presencia auténtica";
    }
    
    private function getEmotionalGuidance()
    {
        switch ($this->emotionalState) {
            case 'sensitive':
                return "Priorizar validación emocional, lenguaje cálido y ritmo lento";
            case 'crisis':
                return "Enfocarse en seguridad, derivación a ayuda humana y lenguaje directo pero compasivo";
            default:
                return "Mantener tono compasivo pero neutral, ritmo moderado";
        }
    }
}