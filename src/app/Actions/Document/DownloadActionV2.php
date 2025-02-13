<?php

declare(strict_types=1);

namespace App\Actions\Document;

use App\Enums\Resources;
use App\Exceptions\Document\DocumentLinkDoesNotExist;
use App\Interfaces\Repository\ContractRepository;
use App\Interfaces\Repository\DocumentRepository;
use App\Interfaces\Repository\FormRepository;
use App\Models\Account;
use Aptive\Component\Http\Exceptions\ForbiddenHttpException;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\Component\Http\Exceptions\NotFoundHttpException;
use Aptive\PestRoutesSDK\Exceptions\ResourceNotFoundException;
use Aptive\PestRoutesSDK\Resources\Contracts\Contract;
use Aptive\PestRoutesSDK\Resources\Documents\Document;
use Aptive\PestRoutesSDK\Resources\Forms\Form;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function basename;
use function fclose;
use function fopen;
use function fpassthru;
use function parse_url;
use function response;

/**
 * @final
 */
class DownloadActionV2
{
    public function __construct(
        private readonly DocumentRepository $repository,
        private readonly ContractRepository $contractRepository,
        private readonly FormRepository $formRepository
    ) {
    }

    /**
     * @param Account $account
     * @param int $documentId
     * @param string $documentType
     *
     * @return StreamedResponse
     *
     * @throws NotFoundHttpException when document cannot be found
     * @throws DocumentLinkDoesNotExist if fetched document does not have download link
     * @throws InternalServerErrorHttpException when API call fails
     * @throws ForbiddenHttpException when document does not belong to customer or visibility is limited
     */
    public function __invoke(Account $account, int $documentId, string $documentType): StreamedResponse
    {
        try {
            $document = $this->getRepository($documentType)
                ->getDocument($account->office_id, $documentId);
        } catch (ResourceNotFoundException $e) {
            throw new NotFoundHttpException(previous: $e);
        }

        if ($this->isDocumentDoesNotBelongsToCustomer($document, $account)) {
            throw new ForbiddenHttpException(
                sprintf('Document "%d" does not belong to customer "%d"', $document->id, $account->account_number)
            );
        }

        if ($document->documentLink === null) {
            throw new DocumentLinkDoesNotExist();
        }

        $fileName = $this->getFileNameFromUrl($document->documentLink);

        return response()->streamDownload(
            function () use ($document) {
                $this->passThruDocument($document->documentLink);
            },
            $fileName
        );
    }

    private function getFileNameFromUrl(string $url): string
    {
        $path = parse_url($url, \PHP_URL_PATH);

        if (!$path) {
            throw new InvalidArgumentException(sprintf('Cannot get filename from URL "%s"', $url));
        }

        return basename($path);
    }

    private function passThruDocument(string $url): void
    {
        $fp = fopen($url, 'rb');

        if ($fp === false) {
            // @codeCoverageIgnoreStart
            throw new InvalidArgumentException(sprintf('File "%s" is not accessible', $url));
            // @codeCoverageIgnoreEnd
        }

        fpassthru($fp);
        fclose($fp);
    }

    private function isDocumentDoesNotBelongsToCustomer(Document|Contract|Form $document, Account $account): bool
    {
        if ($document instanceof Contract) {
            return !in_array($account->account_number, $document->customerIds);
        }

        return $document->customerId !== $account->account_number;
    }

    private function getRepository(string $documentType): DocumentRepository|ContractRepository|FormRepository
    {
        return match ($documentType) {
            Resources::CONTRACT->value => $this->contractRepository,
            Resources::FORM->value => $this->formRepository,
            default => $this->repository,
        };
    }
}
