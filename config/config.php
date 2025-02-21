<?php
return [
    'nyt' => [
        'api_key' => 'YGFEbxRG2GFAZfQXIdcUQ9CgmxcjSYKm', // Replace with your actual API key
        'secret_key' => 'SBAo6WUfxXo5MyZ6',
        'base_url' => 'https://api.nytimes.com/svc/search/v2/articlesearch.json',
    ],
    'cache_enabled' => true, // Enable/disable caching
    'cache_ttl' => 300, // Cache duration in seconds,
    'jwt' => [
        'secret' => 'e3b0c44298fc1c14ab97cf0ddcde1c09b27a6d2ebc41de66289b6f6d30f5b8d6',
        'expiration' => 3600 // 1 hour
    ]
];
