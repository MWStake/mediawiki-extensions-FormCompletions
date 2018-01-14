<?php

/**
 * Copyright (C) 2018  NicheWork, LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark A. Hershberger <mah@nichework.com>
 */

namespace MediaWiki\Extensions\FormCompletions;

use MediaWiki\Extensions\FormCompletions\Completer\Closure;

class CompletionHandler {
	private $handler = [];

	public function registerCompletions( $api ) {
		$config = new Config();
		foreach ( $config->get( 'AvailableCompletions' ) as $key => $completer ) {
			$call = [ $completer, 'getPrefix' ];

			if ( is_object( $completer ) && get_class( $completer ) === "Closure" ) {
				$inst = Closure::getInstance( $api );
				$inst->setPrefix( $key );
				$inst->setCompleter( $completer );
				$this->handler[$key] = $inst;
			} elseif ( is_callable( $call ) ) {
				$this->handler[ call_user_func( $call ) ]
					= $completer;
			} elseif (
				class_exists( $completer )
				&& method_exists( $completer, 'handleCompletion' )
			) {
				$this->handler[$key] = $completer;
				$api->dieWithError(
					[ "apierror-formcompletions-method-not-exist", $completer, 'getPrefix' ]
				);
			} elseif ( !class_exists( $completer ) ) {
				$api->dieWithError(
					[ "apierror-formcompletions-class-not-exist", $completer ]
				);
			} else {
				$api->dieWithError(
					[ "apierror-formcompletions-handler-not-callable", $completer ]
				);
			}
		}
	}

	public function getCompleter( $api, $prefix ) {
		if (
			isset( $this->handler[$prefix] ) &&
			method_exists( $this->handler[$prefix], 'getInstance' )
		) {
			return $this->handler[$prefix]->getInstance( $api );
		}
		$api->dieWithError(
			[ 'apierror-formcompletions-no-handler-for', $prefix ]
		);
	}

	public static function getInstance() {
		return new self;
	}
}
