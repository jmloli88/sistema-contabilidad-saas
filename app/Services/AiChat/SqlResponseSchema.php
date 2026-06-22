<?php

namespace App\Services\AiChat;

use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class SqlResponseSchema extends ObjectSchema
{
    public function __construct()
    {
        parent::__construct(
            name: 'sql_response',
            description: 'SQL query or conversational response',
            properties: [
                new StringSchema(
                    name: 'type',
                    description: 'Either "sql" or "conversational"'
                ),
                new StringSchema(
                    name: 'content',
                    description: 'The SQL query or conversational text'
                ),
            ],
            requiredFields: ['type', 'content'],
        );
    }
}
