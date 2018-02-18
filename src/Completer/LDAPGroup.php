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
namespace MediaWiki\Extensions\FormCompletions\Completer;

use MediaWiki\Extensions\FormCompletions\Completer;

class LDAPGroup extends Completer {
	private $group;

	public static function prefix() {
		return "ldapgroup";
	}

	public function setArg( $group ) {
		$this->group = $group;
	}

	public function handleCompletion( $substr ) {
		return $this->matchData( $this->loadData() );
	}

	private function loadData() {
		$key = $this->finder;
		$page = $this->page;
		return $this->memc->getWithSetCallback(
			$this->memc->makeKey( "form-completions", "load-data", $key ),
			$this->cacheTime,
			function () use ( $page ) {
				$data = array_filter( explode( "\n", $this->loadFile( $page )->getNativeData() ) );
				if ( !is_array( $data ) ) {
					$this->dieWithError( [ "apierror-formcompletions-could-not-parse", $page ] );
				}
				return $data;

			}
		);
	}

	private function matchData( $data ) {
		$pattern = preg_quote( $this->substr, '/' );

		$ret = [];
		if ( !is_array( $data ) ) {
			$this->error( "Should have been an array: "
						  . var_export( $data, true ) );
		}
		array_map(
			function ( $val ) use ( &$ret ) {
				$ret[] = [
					'title' => $val
				];
			},
			array_filter( $data,
						  function ( $line ) use ( $pattern ) {
							  if ( preg_match( "/$pattern/i", $line ) === 1 ) {
								  return true;
							  }
							  return false;
						  } ) );
		return $ret;
	}
}
