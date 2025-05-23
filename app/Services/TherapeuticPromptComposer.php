<?php

namespace App\Services;

use App\Services\EmotionalToneBuilder;

class TherapeuticPromptComposer
{
    public static function buildResponse($userInput, $lang = 'es')
    {
        $intro = EmotionalToneBuilder::getIntro($lang);
        $cierre = EmotionalToneBuilder::getCierre($lang);
        
        // Quitar signos que hacen que la IA hable de forma técnica
        $userInput = trim($userInput);
        $userInput = ucfirst($userInput);

        $formattedInput = wordwrap($userInput, 80, "\n");

        return <<<TEXT
$intro

$formattedInput

$cierre
TEXT;
    }
}
