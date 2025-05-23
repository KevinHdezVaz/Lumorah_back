<?php

namespace App\Services;

class TherapeuticQuestionGenerator
{
    private static $questions = [
        'es' => [
            "¿Qué parte de tu cuerpo está hablando más fuerte hoy? 🌿",
            "¿Hay una emoción que quieras ponerle nombre? ✨",
            "¿Qué necesitarías escuchar ahora mismo… aunque sea en silencio? 🤍",
            "¿Puedes recordar una vez en que te sentiste en paz? ¿Cómo fue? 💫"
        ]
    ];

    public static function getRandomQuestion($lang = 'es')
    {
        return self::$questions[$lang][array_rand(self::$questions[$lang])];
    }
}
