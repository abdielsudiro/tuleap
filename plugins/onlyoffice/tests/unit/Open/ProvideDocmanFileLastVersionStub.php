<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\OnlyOffice\Open;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

/**
 * @psalm-immutable
 */
final class ProvideDocmanFileLastVersionStub implements ProvideDocmanFileLastVersion
{
    /**
     * @param Ok<\Docman_Version>|Err<Fault> $result
     */
    private function __construct(private Ok|Err $result)
    {
    }

    public static function buildWithError(): self
    {
        return new self(Result::err(Fault::fromMessage('Something bad')));
    }

    public static function buildWithDocmanVersion(\Docman_Version $version): self
    {
        return new self(Result::ok($version));
    }

    public function getLastVersionOfAFileUserCanAccess(\PFUser $user, int $item_id): Ok|Err
    {
        return $this->result;
    }
}
