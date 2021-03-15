<?php

namespace EasyAmo\Helpers;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Collections\Leads\LeadsCollection;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Collections\NotesCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Exceptions\AmoCRMApiErrorResponseException;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel;
use AmoCRM\Models\LeadModel;
use AmoCRM\Models\ContactModel;
use AmoCRM\Models\NoteType\CommonNote;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Collections\ContactsCollection;
use AmoCRM\Collections\Leads\Unsorted\FormsUnsortedCollection;
use AmoCRM\Models\Unsorted\FormsMetadata;
use AmoCRM\Models\Unsorted\FormUnsortedModel;

class AmoFunctions
{

    public function initClient(array $config)
    {

        $tokenHelper = new TokenHelper();

        $domain = $config['domain'];
        $clientId = $config['clientID'];
        $clientSecret = $config['clientSecret'];
        $redirectUri = $config['redirect_uri'];

        if (!file_exists(__DIR__ . '/../tmp/token_info.json')):

            $tokenHelper->createConnect($config);

        endif;

        if ($tokenHelper->isTokenFresh())
            $tokenHelper->refreshAndSave($config, $domain);


        $accessToken = $tokenHelper->getToken();

        $apiClient = new AmoCRMApiClient($clientId, $clientSecret, $redirectUri);

        $apiClient->setAccessToken($accessToken)
            ->setAccountBaseDomain($accessToken->getValues()['baseDomain']);

        return $apiClient;

    }

    public function amoContactCreate(string $name, object $apiClient)
    {

        $contact = new ContactModel();


        $contact->setName($name);

        try {
            $contactModel = $apiClient->contacts()->addOne($contact);

        } catch (AmoCRMApiErrorResponseException $e) {
            var_dump($e);
            die;
        }

        $contactId = $contact->getId();

        return $contact;
    }

    public function amoContactUpdate(object $apiClient, int $contactId, array $data)
    {

        $contact = $apiClient->contacts()->getOne($contactId);

        $contactCustomFieldsValues = new CustomFieldsValuesCollection();

        foreach ($data['contacts'] as $key => $subData):

            $cTempVar = 'cont_' . $key;

            $$cTempVar = new TextCustomFieldValuesModel();

            $$cTempVar->setFieldId($subData['id']);

            $$cTempVar->setValues(
                (new TextCustomFieldValueCollection())
                    ->add((new TextCustomFieldValueModel())->setValue($subData['var']))
            );

            $contactCustomFieldsValues->add($$cTempVar);

        endforeach;

        $contact->setCustomFieldsValues($contactCustomFieldsValues);

        try {
            $results = $apiClient->contacts()->updateOne($contact);

        } catch (AmoCRMApiErrorResponseException $e) {

            var_dump($e);
            die;
        }

        return $contact;

    }

    public function amoContactExist(object $apiClient, array $checkData)
    {

        $contact = $apiClient->contacts()->getBy($checkData['ID']);

        $contact = array($contact);

        if (count($contact) > 0)
            return true;
        else
            return false;

    }

    public function amoLeadCreate(object $apiClient, array $initConfig, array $amoConfig, string $leadName)
    {

        $leadsService = $apiClient->leads();

        $lead = new LeadModel();

        $leadCustomFieldsValues = new CustomFieldsValuesCollection();

        foreach ($amoConfig['leads'] as $key => $subData):

            $tempVar = 'lead_' . $key;

            $$tempVar = new TextCustomFieldValuesModel();
            $$tempVar->setFieldId($subData['id']);
            $$tempVar->setValues(
                (new TextCustomFieldValueCollection())
                    ->add((new TextCustomFieldValueModel())->setValue($subData['var']))
            );

            $leadCustomFieldsValues->add($$tempVar);

        endforeach;

        $lead->setCustomFieldsValues($leadCustomFieldsValues);

        $lead->setName($leadName);

        if ($initConfig['pipelineID'] != '') :

            $lead->setPipelineId($initConfig['pipelineID']);

        endif;

        if ($initConfig['responsibleUserId'] !== "")
            $lead->setResponsibleUserId($initConfig['responsibleUserId']);

        $leadsCollection = new LeadsCollection();
        $leadsCollection->add($lead);

        try {
            $lead = $leadsService->addOne($lead);
        } catch (AmoCRMApiException $e) {
            echo "Lead create error: " . $e->getMessage();
            $this->printError($e);
            die;
        }

        return $lead;

    }

    public function amoLeadCreateUnsorted(object $apiClient, array $amoData)
    {

        $unsortedService = $apiClient->unsorted();

        $formsUnsortedCollection = new FormsUnsortedCollection();
        $formUnsorted = new FormUnsortedModel();
        $formMetadata = new FormsMetadata();
        $formMetadata
            ->setFormId($amoData['lead']['formId'])
            ->setFormName($amoData['lead']['formName'])
            ->setFormPage($amoData['lead']['formUrl'])
            ->setFormSentAt(mktime(date('h'), date('i'), date('s'), date('m'), date('d'), date('Y')))
            ->setReferer($_SERVER['HTTP_REFERER'] ? null : 'https://ba24.live')
            ->setIp($_SERVER['REMOTE_ADDR'] ? null : '127.0.0.1');

        $unsortedLead = new LeadModel();
        $unsortedLead->setName($amoData['lead']['leadName']);

        if ($amoData['lead']['price'])
            $unsortedLead->setPrice($amoData['lead']['price']);

        $leadCustomFieldsValues = new CustomFieldsValuesCollection();

        foreach ($amoData['lead']['customFields'] as $key => $subData):

            $tempVar = 'lead_' . $key;

            $$tempVar = new TextCustomFieldValuesModel();
            $$tempVar->setFieldId($subData['id']);
            $$tempVar->setValues(
                (new TextCustomFieldValueCollection())
                    ->add((new TextCustomFieldValueModel())->setValue($subData['var']))
            );

            $leadCustomFieldsValues->add($$tempVar);

        endforeach;

        $unsortedLead->setCustomFieldsValues($leadCustomFieldsValues);

        $unsortedContactsCollection = new ContactsCollection();
        $unsortedContact = new ContactModel();
        $unsortedContact->setName($amoData['contact']['contactName']);
        $contactCustomFields = new CustomFieldsValuesCollection();

        if ($amoData['contact']['phone']):
            $phoneFieldValueModel = new MultitextCustomFieldValuesModel();
            $phoneFieldValueModel->setFieldCode('PHONE');
            $phoneFieldValueModel->setValues(
                (new MultitextCustomFieldValueCollection())
                    ->add((new MultitextCustomFieldValueModel())
                        ->setValue($amoData['contact']['phone'])
                        ->setEnum('WORK')
                    )
            );
            $unsortedContact->setCustomFieldsValues($contactCustomFields->add($phoneFieldValueModel));
        endif;

        if ($amoData['contact']['email']):
            $emailFieldValueModel = new MultitextCustomFieldValuesModel();
            $emailFieldValueModel->setFieldCode('EMAIL');
            $emailFieldValueModel->setValues(
                (new MultitextCustomFieldValueCollection())
                    ->add((new MultitextCustomFieldValueModel())
                        ->setValue($amoData['contact']['email'])
                        ->setEnum('WORK')
                    )
            );

            $unsortedContact->setCustomFieldsValues($contactCustomFields->add($emailFieldValueModel));
        endif;

        $unsortedContactsCollection->add($unsortedContact);

        $formUnsorted
            ->setSourceName($amoData['sourceName'])
            ->setSourceUid($amoData['sourceUid'])
            ->setCreatedAt(time())
            ->setMetadata($formMetadata)
            ->setLead($unsortedLead)
            ->setContacts($unsortedContactsCollection);

        $formsUnsortedCollection->add($formUnsorted);

        try {
            $formsUnsortedCollection = $unsortedService->add($formsUnsortedCollection);
        } catch (AmoCRMApiException $e) {
            $this->printError($e);
            die;
        }

        $leadID = $formUnsorted->toArray()['lead']['id'];

        $notesCollection = new NotesCollection();
        $CommonNote = new CommonNote();
        $CommonNote->setEntityId($leadID)
            ->setText($amoData['noteText']);

        $notesCollection->add($CommonNote);

        try {
            $leadNotesService = $apiClient->notes(EntityTypesInterface::LEADS);

            $notesCollection = $leadNotesService->add($notesCollection);

        } catch (AmoCRMApiException $e) {
            printError($e);
            die;
        }

        return $formUnsorted;

    }

    public function linkContactToLead(object $apiClient, $leadObj, $contactObj)
    {

        $links = new LinksCollection();
        $links->add($leadObj);
        try {
            $apiClient->contacts()->link($contactObj, $links);
        } catch (AmoCRMApiException $e) {
            echo "Contact link error: " . $e->getMessage();

            printError($e);
            die;
        }

        return $links;
    }

    public function printError(AmoCRMApiException $e): void
    {
        $errorTitle = $e->getTitle();
        $code = $e->getCode();
        $debugInfo = var_export($e->getLastRequestInfo(), true);

        $error = <<<EOF
Error: $errorTitle
Code: $code
Debug: $debugInfo
EOF;

        echo '<pre>' . $error . '</pre>';
    }

}