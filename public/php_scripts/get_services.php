<?php
// api/get_services.php

// В реальном приложении эти данные будут приходить из вашей базы данных (таблиц servicios и tarifas)
$services_data = [
    [
        "id" => 1,
        "nombre" => "Motocicleta",
        "icon_class" => "fas fa-motorcycle",
        "max_peso_kg" => 15,
        "max_volumen_m3" => 0.06, // 40см * 40см * 40см = 64000 см³ = 0.064 м³
        "tarifa_base" => 1000,
        "costo_km" => 30,
        "costo_kg" => 50
    ],
    [
        "id" => 5, // Используем ID из вашего примера
        "nombre" => "Motocicleta Rápido",
        "icon_class" => "fas fa-bolt",
        "max_peso_kg" => 15,
        "max_volumen_m3" => 0.06,
        "tarifa_base" => 2000, // Тариф выше за скорость
        "costo_km" => 40,
        "costo_kg" => 60
    ],
    [
        "id" => 4,
        "nombre" => "Furgón",
        "icon_class" => "fas fa-shuttle-van",
        "max_peso_kg" => 500,
        "max_volumen_m3" => 3, // 3 кубических метра
        "tarifa_base" => 8000,
        "costo_km" => 100,
        "costo_kg" => 30
    ],
    [
        "id" => 2,
        "nombre" => "Pickup",
        "icon_class" => "fas fa-truck-pickup",
        "max_peso_kg" => 800,
        "max_volumen_m3" => 2.5,
        "tarifa_base" => 9000,
        "costo_km" => 120,
        "costo_kg" => 40
    ],
    [
        "id" => 3,
        "nombre" => "Camión",
        "icon_class" => "fas fa-truck",
        "max_peso_kg" => 5000,
        "max_volumen_m3" => 15,
        "tarifa_base" => 15000,
        "costo_km" => 200,
        "costo_kg" => 20
    ]
];

// Устанавливаем заголовок, чтобы браузер понял, что это JSON
header('Content-Type: application/json');
echo json_encode($services_data);