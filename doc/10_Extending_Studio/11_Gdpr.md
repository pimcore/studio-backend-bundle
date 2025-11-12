# Extending GDPR Data Providers

The GDPR Data Provider system provides a centralized interface to find and export personal data from any part of your Pimcore application. You can add new data sources (like Data Objects, Assets, Users, or any custom entity) by creating your own provider.

New providers are created by implementing the `Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider\DataProviderInterface` and tagging your class as a service with `pimcore.studio_backend.gdpr_data_provider`.

## How does it work

The `GdprManagerService` acts as the central coordinator for all registered providers. It automatically discovers your tagged service.

### 🔎 For Searching

1.  The manager loads all tagged providers to build the search interface. It calls your provider's `getName()`, `getKey()`, `getSortPriority()`, and `getAvailableColumns()` methods.
2.  When a user performs a search, the manager first checks `getRequiredPermission()` to see if the current user is allowed to use your provider.
3.  If permitted, the manager calls your provider's `findData()` method, passing the user's search terms. The results are then displayed in the grid.

### For Exporting

The export process is a two-step flow handled by your provider:

1.  **Start Job:** The manager calls `startJobExecution()`. Your provider is responsible for starting a background process (e.g., a Symfony Messenger job) and immediately returning a **unique Job ID** (as a string).
2.  **Get File:** When the user clicks the download button for that job, the manager loops through all providers and calls `ownsJob($jobId)` on each one to find the correct owner.
    -   Once the owner is found, the manager calls `getExportFile($jobId)`.
    -   Your provider is then responsible for finding the completed job's file, checking specific permissions (e.g., "does this user own this job?"), and streaming the file back as a `StreamedResponse`.

## Example Data Provider

Here is a example of a provider that searches for **Customer** data objects.

```php

final class CustomerObjectProvider implements DataProviderInterface
{

    public function __construct(
    ) {
    }

    /**
     * A unique key for your provider.
     */
    public function getKey(): string
    {
        return 'customers';
    }

    /**
     * A human-friendly name shown in the UI.
     */
    public function getName(): string
    {
        return 'Customer Objects';
    }

    /**
     * Sort order for the UI. Higher numbers appear first.
     */
    public function getSortPriority(): int
    {
        return 10;
    }

    /**
     * The general permission needed to use this provider.
     */
    public function getRequiredPermission(): UserPermissions
    {
        // Users must have 'objects' permission to use this provider
        return UserPermissions::OBJECTS;
    }

    /**
     * Defines the columns for the search result grid.
     * The 'key' must match the key in the array returned by findData().
     *
     * @return GdprDataColumn[]
     */
    public function getAvailableColumns(): array
    {
        return [
            new GdprDataColumn('id', 'ID'),
            new GdprDataColumn('email', 'Email Address'),
            new GdDprDataColumn('path', 'Full Path'),
        ];
    }

    /**
     * The core search logic.
     *
     * @return array<array<string, mixed>>
     */
    public function findData(?SearchTerms $terms): array
    {
      //Finds the data matched with search terms

    }

    /**
     * Starts the background export job.
     *
     * @return string The unique Job ID
     */
    public function startJobExecution(GdprStructuredSearchRequest $request): string
    {
        $jobId = '1';//Create a job id

        return $jobId;
    }

    /**
     * A quick check to see if this provider is responsible for a job.
     * This should be a fast check (e.g., checking a job type or ID prefix).
     */
    public function ownsJob(int $jobRunId): bool
    {
        // return $this->jobService->doesJobExist?

    }

    /**
     * Finds the completed job file and streams it.
     * This is only called after ownsJob() returns true.
     */
    public function getExportFile(int $jobRunId): StreamedResponse
    {
        // 1. Find the job in your storage

        // 2. If the job doesn't exist (or isn't yours), you MUST throw this
        // if ($job === null) {
        //     throw new NotFoundException('Export job not found');
        // }


        // 3. Find the file on disk (or stream from S3, etc.)
        // $filePath = $this->fileService->getFilePath($job->getFileName());
        // if (!$this->fileService->exists($filePath)) {
        //     throw new NotFoundException('Export file is missing or not yet generated.');
        // }

        //Return response
    }
}
```
