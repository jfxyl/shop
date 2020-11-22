<?php

return [
    'alipay' => [
        'app_id'         => '2016091700533052',
        'ali_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAsySphuoXcgz4puzbHnNU4SEtq3BbjDxeaizwgo2LSwF/wKoO8/rzseLy+tHXrgOKhOXNynC2ewGsoHta++L7tnft3GI4OvU2EEnbb7PMXQItziYqNozplJp+cG9qzjLnUeAI55Qgsirb5Wj72ffDIfApYdO2Mz6rc2P+A6znBl+8e8zD6X17ftA+gXmyGKcNxx1kS9Qc+TJQpOx6YeKCykIDpSTy47SDPeETF7w1zqthVERm9zZMXM7rJCnYdvl7mOGzlaUapzkpc6NljrHgeKaXdst4aZzWdYnrIww6wdbHcpTEZQLDGBd/WjxLkT7d8Ox4qIKJ1ogpTps/hp1sXQIDAQAB',
        'private_key'    => 'MIIEogIBAAKCAQEAgPolSTiRj6J0P3yGdG9N+1HKua4RtkLWTaUMQB8XEnVVHte7Hz163+CjVy0q+/l/YP5zjvG0N8I4hyLpt6G5TLzDOdJOrCQeOb5ClwUr9xTbiFOo6jhsVxG+40FGr8Kt1SZy/mR7lxCdj+5PRZgaIivreT0TR/wk33oyFUyzM6qPv/4Z7W3QbCwMoTcA4yeB0wIC2h6iQQl6i8sQE5zKpSW3TO+1RQ3wW+IDWzFsVXKgKDoazJi1+mIXZcCrd9ylLvIDMQUPV74faIKSzQTzF7w6/DAnXwGBpEIDpRsvGPeUVzepdOdirPJRkBTvlSAB7wzwls/6imNojNJmmwIa8QIDAQABAoIBADY9/XRf0AfiQJV6n2lUbi1l5qZUaKqITWx2H0LQUHm40sWX4OBwkL6a0NcW7d7uvP1jxeG2ER7qFa/vpO9PmoiFUm183w1SW0vZklPFHwpYwUMCPCXU+OtdUTRt/XZpn4XnF7GZPMj5eepQRRJ1t3frlKp2XyeFCVTbJTkD+tGdKm4KzSsjqC1K/MGfSI9v/+c/HPaMKgKIEL0c4bur2pPoNgyOyx+TZmmwLBBKKR/JwEaKVxReKOrcbeQX6qrGfA0jHsiao5AtvC0yFV0PqCRtWGJAwy/TIzdfoLiQgKk5kjcfAEC3PJ9eRRUYUUiLiWdNWkoGsBMAU4HF707AFoECgYEA5z6mHy0cT9e5mvv8apbEU1YdWA2xLQPDrZOa+Wg8v/uuZ5tWUAc4913WqsRw/tKy3j/2ENkc9wdPhVbS2Ff7UiQ8CmXDx7YFncGycHxIQr85W62zw69db2cxX+WsTuLniYTfmgbkX5Lx4SzxvpHKkaQywmK4AZ++mwbvJlzyKlkCgYEAjsjQV7nm4XJLAYgjU+/ozpqf285UmQLGcsVGEzWH7DL14//yOvVcZNrWOFrNyiH1WXf3YMUpQVO7KXzKgQxKQRUCgcc18lvhXvrGhmASVT8tizcktucITJ59Zysu0DgyYGCu0UHqlyYpMPSAqfSO1wL0eEEgCJRMIifcEglsMlkCgYAIu6H0bXSzQzdcNgX7VIRHjWoASEwXohvCs19X6erZaTzV5tTkotEw7ldMDa0iwnxEzm+RhVGFtr33ECdYSkJQgNPPpLY3FZWytnzxqI/mDWiyIKY4Tqgdq+z+bSMLu5/43o6/N2Fqhpch5NugUcsvot7T3nyKeyjsm99uOHZgyQKBgDESDKAJkLJsMCDfo5yGN9FBTK0i9On9DSyGZbXWUNc5EE6COMJQbqdume6WLmFIWGSeGRNVzv7XgtwYOhQtoBtL2Ce3ye936jVVJAMsY0COzN0qX2DId461bU2WhqkilxWORKY/7Bp0D/X2IX3HQYvdRR1K7HJmXN/kKPq96ERJAoGAL8qLT61V1EPhT+QCl/Ajvs2kYUWnth0LjNFSXvW24CE4Qbb8rR/JlcBl11el9RxtK55lZ5dm0f/NeIvoyuyfAKNENuvYg2Ox2yv8IE3ayZErAtydTYlrhivc9aR5EZjw0lyDDROmN0PSTs1ROy4vt1TQdvbZYMYZP7GXArzCTJo=',
        'log'            => [
            'file' => storage_path('logs/alipay.log'),
        ],
    ],

    'wechat' => [
        'app_id'      => '',
        'mch_id'      => '',
        'key'         => '',
        'cert_client' => '',
        'cert_key'    => '',
        'log'         => [
            'file' => storage_path('logs/wechat_pay.log'),
        ],
    ],
];
