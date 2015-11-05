<?php declare(strict_types=1);

namespace Spot\Cms\ApiCall\Like;

use Particle\Validator\Validator;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Http\Message\RequestInterface as HttpRequest;
use Psr\Http\Message\ResponseInterface as HttpResponse;
use Ramsey\Uuid\Uuid;
use Spot\Cms\Application\ApiCallInterface;
use Spot\Cms\Application\Request\Message\BadRequest;
use Spot\Cms\Application\Request\Message\RequestInterface;
use Spot\Cms\Application\Request\RequestException;
use Spot\Cms\Application\Response\Message\ResponseInterface;
use Zend\Diactoros\Response\JsonResponse;

/**
 * An example/reference implementation of a full HttpRequest->HttpResponse run
 */
class Like implements ApiCallInterface
{
    /** @var  \PDO */
    private $db;

    /** @var  array */
    private $user;

    /** @var  \Ramsey\Uuid\Uuid */
    private $uuid;

    /** @var  int */
    private $stateChange;

    public function __construct(\PDO $db, array $user)
    {
        $this->db = $db;
        $this->user = $user;
    }

    /** {@inheritdoc} */
    public function parseHttpRequest(ServerHttpRequest $httpRequest) : RequestInterface
    {
        $validator = new Validator();
        $validator->required('uuid')->uuid();
        $validator->required('state_change')->integer()->between(0, 1);

        $validationResult = $validator->validate((array) $httpRequest->getParsedBody());
        if (!$validationResult->isValid()) {
            throw new RequestException(new BadRequest());
        }
        $values = $validationResult->getValues();

        $this->uuid = Uuid::fromString($values['uuid']);
        $this->stateChange = intval($values['state_change']);

        return $this;
    }

    /** {@inheritdoc} */
    public function getRequestName() : string
    {
        return 'like';
    }

    /** {@inheritdoc} */
    public function executeRequest(RequestInterface $request, HttpRequest $httpRequest) : ResponseInterface
    {
        if ($this->stateChange === 0) {
            $this->db->prepare('DELETE FROM likes WHERE uuid = :uuid AND user_uuid = :user_uuid')
                ->execute(['uuid' => $this->uuid, 'user_uuid' => $this->user['user_uuid']]);
        } else {
            $this->db->prepare('INSERT INTO likes (uuid, user_uuid) VALUES (:uuid, :user_uuid)')
                ->execute(['uuid' => $this->uuid, 'user_uuid' => $this->user['user_uuid']]);
        }

        return $this;
    }

    /** {@inheritdoc} */
    public function getResponseName() : string
    {
        return 'like';
    }

    /** {@inheritdoc} */
    public function generateResponse(ResponseInterface $request, HttpRequest $httpRequest) : HttpResponse
    {
        return new JsonResponse(['success' => true]);
    }
}
