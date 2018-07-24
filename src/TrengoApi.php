<?php

namespace Keesschepers\TrengoApi;

use GuzzleHttp;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ResponseException;

class TrengoApi
{
    private $token;
    private $client;

    public function __construct($token)
    {
        $this->token = $token;
    }

    private function createClient()
    {
        if (null == $this->client) {
            $this->client = new GuzzleHttp\Client(
                [
                    'base_uri' => 'https://app.trengo.eu/api/v1/',
                    'headers' => ['token' => $this->token, 'Content-type' => 'application/json'],
                ]
            );
        }

        return $this->client;
    }

    public function createProfile($name, User $user)
    {
        $client = $this->createClient();

        try {
            $response = $client->post(
                'profiles',
                [
                    'json' => [
                        'name' => iconv('utf-8', 'ascii//TRANSLIT', $name),
                        'created_by' => $user->getId(),
                    ]
                ]
            );

            $profile = json_decode((string)$response->getBody(), true);

            return (new Profile())->setId($profile['id']);
        } catch (RequestException $e) {
            throw new ApiException('Creating trengo profile failed.', 0, $e);
        }
    }

    public function searchContactByPhone($phoneNumber)
    {
        return $this->searchContact(null, null, $phoneNumber);
    }

    public function searchContactByEmail($email)
    {
        return $this->searchContact(null, $email);
    }

    public function searchContact($q = null, $email = null, $phone = null)
    {
        if (is_null($q) && is_null($email) && is_null($phone)) {
            throw new ApiException('Either a phrase, email or phone number should be given.');
        }

        $client = $this->createClient();

        try {
            $response = $client->get(
                'contacts/search',
                [
                    'query' => [
                        'q' => $q,
                        'email' => $email,
                        'phone' => $phone,
                    ]
                ]
            );

            $contact = json_decode((string)$response->getBody(), true);

            if (empty($contact)) {
                return null;
            }

            return (new Contact())
                ->setId($contact['id'])
                ->setFullName($contact['full_name'])
                ->setPhone(array_key_exists('phone', $contact) ? $contact['phone'] : null)
                ->setEmail(array_key_exists('email', $contact) ? $contact['email'] : null);

        } catch (RequestException $e) {
            throw new ApiException('Creating trengo profile failed.', 0, $e);
        }
    }

    public function attachContact(Profile $profile, Contact $contact, $type)
    {
        switch ($type) {
            case 'VOIP':
                if (!is_null($contact->getEmail())) {
                    throw new ApiException('Cannot add email contact as VOIP contact');
                }
                break;
            case 'EMAIL':
                if (!is_null($contact->getPhone())) {
                    throw new ApiException('Cannot add phone contact as EMAIL contact');
                }
                break;
        }

        $client = $this->createClient();

        try {
            $response = $client->post(
                sprintf('profiles/%s/contacts', $profile->getId()),
                [
                    'json' => [
                        'contact_id' => $contact->getId(),
                        'type' => $type,
                    ]
                ]
            );

            return null;
        } catch (RequestException $e) {
            throw new ApiException('Creating trengo profile failed.', 0, $e);
        }
    }

    public function createContact($email = null, $phone = null, $name = null, array $customFieldData = [])
    {
        if (!is_null($email) && !is_null($phone)) {
            throw new ApiException('Either phone or email should be given not both');
        }

        $client = $this->createClient();

        $data = [
            'email' => $email,
            'phone' => $phone,
            'name' => iconv('utf-8', 'ascii//TRANSLIT', $name),
            'custom_field_data' => $customFieldData,
        ];

        try {
            $response = $client->post(
                'contacts',
                [
                    'json' => array_filter($data, function($value) { return !is_null($value); }),
                ]
            );

            $contact = json_decode((string)$response->getBody(), true);

            if ($contact['type'] === 'success') {
                $contact = $contact['contact'];
            }

            return (new Contact())
                ->setId($contact['id'])
                ->setFullName($contact['full_name'])
                ->setPhone(array_key_exists('phone', $contact) ? $contact['phone'] : null)
                ->setEmail(array_key_exists('email', $contact) ? $contact['email'] : null);

        } catch (RequestException $e) {
            throw new ApiException('Creating trengo profile failed.', 0, $e);
        } catch (ServerException $e) {
            throw new ApiException('Creating trengo profile failed.', 0, $e);
        }
    }

    public function updateContact(Contact $contact, $email = null, $phone = null, $name = null, array $customFieldData = [])
    {
        if (is_null($contact->getId())) {
            throw new ApiException('Impossible to update contact without ID');
        }

        if (!is_null($email) && !is_null($phone)) {
            throw new ApiException('Either phone or email should be given not both');
        }

        $client = $this->createClient();

        $data = [
            'email' => $email,
            'phone' => $phone,
            'name' => iconv('utf-8', 'ascii//TRANSLIT', $name),
            'custom_field_data' => $customFieldData,
        ];

        try {
            $response = $client->post(
                sprintf('contacts/%s', $contact->getId()),
                [
                    'json' => array_filter($data, function($value) { return !is_null($value); }),
                ]
            );

            $contact = json_decode((string)$response->getBody(), true);

            return (new Contact())
                ->setId($contact['id'])
                ->setFullName($contact['full_name'])
                ->setPhone(array_key_exists('phone', $contact) ? $contact['phone'] : null)
                ->setEmail(array_key_exists('email', $contact) ? $contact['email'] : null);

        } catch (RequestException $e) {
            throw new ApiException('Creating trengo profile failed.', 0, $e);
        } catch (ServerException $e) {
            throw new ApiException('Creating trengo profile failed.', 0, $e);
        }
    }
}
