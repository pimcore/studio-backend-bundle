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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Constant;

/**
 * @internal
 */
final readonly class PublicTranslations
{
    public const PUBLIC_KEYS = [
        'aria.login-form-additional-logins.remember-me-checkbox',
        'login-form.remember-me',
        'login-form.forgot-password',
        'login-form.login',
        'login-form-additional-logins.or',
        'aria.login-form-additional-logins.additional-login-provider',
        'login-form.username',
        'login-form.password',
        'forgot-password-form.success-message',
        'forgot-password-form.username',
        'forgot-password-form.reset-password',
        'forgot-password-form.back',
        'forgot-password-form.username.placeholder',
        // Error dialog shown when a login attempt fails
        'error',
        'alert-modal.ok-text',
        // Two-factor code and setup steps, shown before the login is finished
        'two-factor-form.description',
        'two-factor-form.code.placeholder',
        'two-factor-form.code.required',
        'two-factor-form.code.invalid-format',
        'two-factor-form.verify',
        'two-factor-form.error',
        'two-factor-form.too-many-attempts',
        'two-factor-form.back',
        'two-factor-setup-form.description',
        'two-factor-setup-form.manual-entry',
        'two-factor-setup-form.confirm',
        'two-factor-setup-form.load-error',
        'two-factor-setup-form.expired',
    ];
}
