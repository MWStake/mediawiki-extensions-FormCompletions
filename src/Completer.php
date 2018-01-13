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

abstract class Completer {
	abstract public static function prefix();
	abstract public function setArg( $arg );
	abstract public function handleCompletion( $substr );

	protected $memc;
	protected $config;
	protected $api;

	public static function getInstance( API $api ) {
		$class = get_called_class();
		$self = new $class();
		$self->memc = wfGetCache( CACHE_ANYTHING );
		$self->config = Config::newInstance();
		$self->api = $api;
		return $self;
	}
}
