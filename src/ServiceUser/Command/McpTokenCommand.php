<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\ServiceUser\Command;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AbstractApiException;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Service\McpTokenServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function ctype_digit;
use function date;
use function is_string;
use function sprintf;

/**
 * Issues or revokes MCP access tokens for a service user, the interim credential until OAuth
 * client_credentials is enabled.
 *
 * @internal
 */
#[AsCommand(
    name: 'studio-backend-bundle:service-user:mcp-token',
    description: 'Issue an MCP access token for a service user, or revoke all of its tokens.'
)]
final class McpTokenCommand extends Command
{
    private const string ARGUMENT_ID = 'id';

    private const string OPTION_TTL_DAYS = 'ttl-days';

    private const string OPTION_REVOKE = 'revoke';

    private const int DEFAULT_TTL_DAYS = 30;

    public function __construct(
        private readonly McpTokenServiceInterface $mcpTokenService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(self::ARGUMENT_ID, InputArgument::REQUIRED, 'ID of the service user')
            ->addOption(
                self::OPTION_TTL_DAYS,
                null,
                InputOption::VALUE_REQUIRED,
                sprintf('Lifetime of the token in days, 1 to %d', McpTokenServiceInterface::MAX_TTL_DAYS),
                (string) self::DEFAULT_TTL_DAYS
            )
            ->addOption(
                self::OPTION_REVOKE,
                null,
                InputOption::VALUE_NONE,
                'Revoke every MCP access token of the service user instead of issuing one'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $id = $input->getArgument(self::ARGUMENT_ID);
        $ttlDays = $input->getOption(self::OPTION_TTL_DAYS);
        if (!is_string($id) || !ctype_digit($id) || !is_string($ttlDays) || !ctype_digit($ttlDays)) {
            $io->error('The service user ID and the lifetime must be whole numbers.');

            return Command::INVALID;
        }

        try {
            if ($input->getOption(self::OPTION_REVOKE) === true) {
                $this->mcpTokenService->revokeTokens((int) $id);
                $io->success(sprintf('Revoked every MCP access token of service user %d.', (int) $id));

                return Command::SUCCESS;
            }

            $token = $this->mcpTokenService->issueToken((int) $id, (int) $ttlDays);
        } catch (AbstractApiException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->writeln($token->getToken());
        $io->note(sprintf(
            'The token is shown only now and expires on %s. Store it in a secret store.',
            date('Y-m-d H:i:s T', $token->getExpiresAt())
        ));

        return Command::SUCCESS;
    }
}
