<?php

namespace App\Services;

class EmotionalToneBuilder
{
    private static $templates = [
        'es' => [
            'intro' => [
                "Respira profundo… estoy contigo 🌿",
                "Tómate tu tiempo… este es un espacio seguro ✨",
                "Aquí puedes soltar… sin prisa, sin juicio 🤍",
                "Siente tu cuerpo… y deja que hable con calma 🌬️"
            ],
            'cierre' => [
                "¿Qué está presente en tu corazón ahora? 💫",
                "Estoy aquí… cuando lo sientas, seguimos 🕊️",
                "Puedes descansar en esta pausa… yo te acompaño 🤍",
                "Todo lo que necesitas está dentro de ti… y aquí hay espacio para verlo 🌟"
            ]
        ]
    ];

    public static function getIntro($lang = 'es') {
        $templates = [
            'es' => [
                "Encantado de conocerte, [Nombre] 🌿",
                "Hola [Nombre], soy Lumorah 💫",
                "[Nombre], bienvenido a este espacio seguro ✨"
            ]
        ];
        return str_replace('[Nombre]', '', $templates[$lang][array_rand($templates[$lang])]);
    }

 

    public static function getCierre($lang = 'es')
    {
        return self::$templates[$lang]['cierre'][array_rand(self::$templates[$lang]['cierre'])];
    }
}
