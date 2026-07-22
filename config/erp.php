<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Integração de Tabelas do ERP Compartilhado
    |--------------------------------------------------------------------------
    |
    | Estas configurações mapeiam os nomes das tabelas de entidades globais
    | que são compartilhadas entre este módulo de Timesheet e o ERP raiz da
    | empresa. O fallback padrão assume o prefixo 'produtividade_'.
    |
    */

    'tabelas' => [
        'colaborador' => env('DB_TABLE_COLABORADOR', 'produtividade_colaborador'),
        'projeto'     => env('DB_TABLE_PROJETO', 'produtividade_projeto'),
        'veiculo'     => env('DB_TABLE_VEICULO', 'produtividade_veiculo'),
    ],
];
