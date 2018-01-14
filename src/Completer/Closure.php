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

use MediaWiki\Extensions\FormCompletions\CompleterInterface;
use MediaWiki\Extensions\FormCompletions\API;
use MediaWiki\Extensions\FormCompletions\Config;

class Closure implements CompleterInterface {
	protected $completer;
	protected $arg;
	protected $prefix;
	protected static $instances = [];

	/**
	 * Prefix and arg are the same for closures.
	 */
	public function setPrefix( $key ) {
		$this->prefix = $key;
	}

	public function setArg( $key ) {
		$this->arg = $key;
	}

	public static function getPrefix() {
		throw new \MWException( "not needed" );
	}

	public function setCompleter( $completer ) {
		if ( !isset( self::$instances[ $this->prefix ] ) ) {
			self::$instances[ $this->prefix ] = $completer;
			self::$instances[ 'Hydro' ] = $completer;
		}
	}

	public function handleCompletion( $substr ) {
		return call_user_func( self::$instances[ $this->arg ], $substr );
	}

	protected $memc;
	protected $config;
	protected $api;

	public static function getInstance( API $api ) {
		$self = new self();
		$self->memc = wfGetCache( CACHE_ANYTHING );
		$self->config = Config::newInstance();
		$self->api = $api;
		return $self;
	}
}
