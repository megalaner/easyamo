<?php

require_once __DIR__ . "/vendor/autoload.php";

$authConfig =
    [
        'domain' => 'ba24tech.amocrm.ru', // Your AmoCRM domain account
        'clientSecret' => 'Pw7o03ByRJHrQiSiIVcwQPeuO4H21sMclXdpqyPeMQvvhLSPLgElmaliehpZksc3', // Your client secret from custom AmoCRM integration
        'clientID' => 'b67b909d-2a15-4aea-a34d-7642c4049663', //  Your client identifier from custom AmoCRM integration
        'oAuthCode' => 'def502007f07cb09bc6cb4071e1b6fe3c8696ce710291e97402e9847f5eb787978b0e74a3f47dbfb802bbc0c27c52c802d2935c94b2933065f2e698982d438c99956dbef6e4311366b50602c4ec5c42b743d766a8e625c433cc1ae00ef7b2a32b06955c6d585959f2495273d493a0105cf9a0ab6bfab323014313a989d81af0be76e62afd2cdb778637f0624a2716011928ff45f2529f0488443ba302f5dc532d52907e16893792d9ad5f89b6346af85dd607edf5380c625a48bd1ef02e6158ef1ee500a5691bf260b8ba79b711b5056d3f73e6009b6e551a11633ce4b6ee9e3239b3ed79b5049b0b5b00c11af55e11c54983de397f261eec9701decca8addde18822581723bcb42685e9b46bc6d370db3d36949fc7b78d58b6b2de44dfe419e8e5d29b482f5f2eb549982cdab3ce42d0c5cde52100780683c6408219c3c6e58462c1f6c6c370582061d1a7f02ec9753d58bfd9105edf64e4632177001736dbb2985d744471975ef54d7a6714a3520e1370d9f1cce2f933d88bad0ba30720bcac46ee2786e5e268e7754d3be316dfaf12f33e3b7d30dabb11ed81904d69bca743b49423910736f61de7fb505795269b8eba72eb75fe5f2ca29877d', //  Your oAuth code from custom AmoCRM integration
        'redirect_uri' => 'https://ba24.live', // URI you've provided in your custom AmoCRM integration settings
        'pipelineID' => '', // Pipeline identifier where new leads will come to
    ];

$amoData = [

    'sourceName' => 'testname',
    'sourceUid' => 'testuid',

    'lead' =>
        [
            'leadName' => 'Lead name',
            'formId' => 'Form ID',
            'formName' => 'Form name',
            'formUrl' => 'https://form.url',
            'price' => 1001,
            'customFields' =>
                [
                    /*                  '1' =>
                                          [
                                              'id' => '333963', // See in https://your_domain.amocrm.ru/api/v4/leads/custom_fields
                                              'var' => 'text 1'
                                          ],
                                      '2' =>
                                          [
                                              'id' => '333957',
                                              'var' => 'text 2'
                                          ],
                                      'etc' =>
                                          [

                                              'id' => '333959',
                                              'var' => 'text 3'

                                          ]*/
                ]
        ],

    'contact' => [
        'contactName' => 'Contact name',
        'phone' => '+79998887766',
        'email' => 'email@testgmail.com',
    ],

    'noteText' => 'Note text test'

];

$amoHelper = new EasyAmo\Helpers\AmoFunctions;

$apiClient = $amoHelper->initClient($authConfig);

$newLead = $amoHelper->amoLeadCreateUnsorted($apiClient, $amoData);

var_dump($newLead);
