<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/** Contrôleur de base de l'application : tous les contrôleurs en héritent. Il apporte l'autorisation (policies) et la validation de requêtes. */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
