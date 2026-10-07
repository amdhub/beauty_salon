<?php

return [
    'uuid' => 'La référence de cette opération est invalide. Rechargez la page.',
    'enum' => 'La valeur de :attribute est invalide.',
    'required_if' => 'Le champ :attribute est obligatoire pour cette sélection.',
    'date_format' => 'Le champ :attribute doit respecter le format :format.',
    'required' => 'Le champ :attribute est obligatoire.', 'string' => 'Le champ :attribute doit être un texte.',
    'email' => 'Saisissez une adresse e-mail valide.', 'unique' => 'Cette valeur pour :attribute est déjà utilisée (y compris les fiches archivées).',
    'exists' => 'La valeur sélectionnée pour :attribute n’est pas disponible.', 'date' => 'Le champ :attribute doit être une date valide.',
    'before_or_equal' => 'Le champ :attribute doit être antérieur ou égal à :date.', 'boolean' => 'La valeur de :attribute est invalide.',
    'integer' => 'Le champ :attribute doit être un nombre entier.', 'decimal' => 'Le champ :attribute doit contenir au maximum :decimal décimales.',
    'min' => ['numeric' => 'Le champ :attribute doit être supérieur ou égal à :min.', 'string' => 'Le champ :attribute doit contenir au moins :min caractères.', 'array' => 'Sélectionnez au moins :min élément(s) pour :attribute.'],
    'max' => ['numeric' => 'Le champ :attribute ne doit pas dépasser :max.', 'string' => 'Le champ :attribute ne doit pas dépasser :max caractères.', 'file' => 'Le fichier ne doit pas dépasser :max Ko.', 'array' => 'Le champ :attribute ne doit pas dépasser :max éléments.'],
    'regex' => 'Le format de :attribute est invalide.', 'in' => 'La valeur de :attribute est invalide.', 'array' => 'Le champ :attribute doit être une liste.',
    'confirmed' => 'La confirmation de :attribute ne correspond pas.', 'current_password' => 'Le mot de passe actuel est incorrect.',
    'image' => 'Le fichier doit être une image.', 'mimes' => 'Le fichier doit être au format :values.', 'alpha_dash' => 'Utilisez uniquement lettres, chiffres, tirets et underscores.', 'distinct' => 'Les valeurs de :attribute doivent être distinctes.',
    'password' => ['letters' => 'Le mot de passe doit contenir au moins une lettre.', 'numbers' => 'Le mot de passe doit contenir au moins un chiffre.'],
    'attributes' => ['amount' => 'montant reçu', 'discount' => 'remise', 'payment_method' => 'mode de paiement', 'items' => 'panier', 'customer_id' => 'client', 'first_name' => 'prénom', 'last_name' => 'nom', 'name' => 'nom', 'phone' => 'téléphone', 'secondary_phone' => 'autre téléphone', 'password' => 'mot de passe', 'email' => 'e-mail', 'price' => 'prix', 'base_price' => 'prix de base', 'service_category_id' => 'catégorie', 'default_duration' => 'durée', 'estimated_duration' => 'durée', 'birth_date' => 'date de naissance', 'position' => 'fonction', 'roles' => 'rôles'],
];
