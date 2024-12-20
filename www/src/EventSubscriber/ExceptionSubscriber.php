<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestStack    $request,
        private readonly LoggerInterface $customErrorLogLogger,
    )
    {
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        $response = new Response();

        if ($exception instanceof HttpExceptionInterface) {
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace($exception->getHeaders());
        } else {
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->customErrorLogLogger->info($this->request->getCurrentRequest());
        $this->customErrorLogLogger->error($this->responseForDevAndLog($exception, clone $response));

        if ($_SERVER['APP_ENV'] == "dev"){
            $response = $this->responseForDevAndLog($exception, $response);
        } elseif ($_SERVER['APP_ENV'] == "prod"){
            $response = $this->responseForProd($exception, $response);
        }

        $event->setResponse($response);

    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.exception' => 'onKernelException',
        ];
    }

    private function responseForDevAndLog(Throwable $exception, Response $response): Response
    {
        $content = [];
        $content["file"] = $exception->getFile();
        $content["statusCode"] = $response->getStatusCode();
        $content["code"] = $exception->getLine() . '(' . $exception->getCode() . ')';
        $content["uri"] = $this->request->getCurrentRequest()->getRequestUri();
        $content["message"] = $exception->getMessage();
        $content["trace"] = $exception->getTrace() ?? null;

        $response->headers->set("Content-Type","application/json");
        $response->setContent(json_encode(["error" => $content]));

        return $response;
    }

    private function responseForProd(Throwable $exception, Response $response): Response
    {
        $content = [];
        $content["statusCode"] = $response->getStatusCode();
        $content["code"] = $exception->getLine() . '(' . $exception->getCode() . ')';
        $content["uri"] = $this->request->getCurrentRequest()->getRequestUri();
        $content["message"] = ($response->getStatusCode() < 500) ? $exception->getMessage() : "Internal Server Error";

        $response->headers->set("Content-Type","application/json");
        $response->setContent(json_encode(["error" => $content]));

        return $response;
    }


}