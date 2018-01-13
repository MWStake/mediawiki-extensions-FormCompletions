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

use ApiBase;
use Title;
use WikiPage;
use MediaWiki\Extensions\FormCompletions\Completer\WikiPage as WPCompleter;

class API extends ApiBase {
	private $substr;
	private $finder;
	private $mapPage;
	private $cacheTime;
	private $debug;
	private $cacheOnly;
	private $libkey;
	private $memc;
	private $retValue;
	private $completer;

	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName );
	}

	public function execute() {
		if ( $this->setup() ) {
			# Lookup substring
			$this->handleCompletion();
		}
		$this->returnResults();
	}

	private function setup() {
		$config = Config::newInstance();
		$params = $this->extractRequestParams();
		$this->substr = $params['substr'];
		$this->finder = $params['finder'];
		$this->mapPage = $config->get( Config::COMPLETION_MAP );
		$this->cacheTime = $config->get( Config::CACHE_TIME );
		$this->debug = $config->get( Config::DEBUG );
		$this->cacheOnly = false;
		$this->libkey = "libkey";
		$this->memc = wfGetCache( CACHE_ANYTHING );
		$this->retValue = null;
		$this->handler = CompletionHandler::getInstance();
		$this->handler->registerCompletions();

		return $this->verifyParameters();
	}

	private function verifyParameters() {
		if ( strlen( $this->substr ) < 3 ) {
			# More than 3 charaacters needed
			$this->retValue = "...";
			return false;
		}

		$finder = $this->finder;
		$this->library
			= $this->loadPage( $this->mapPage )->getData()->getValue();
		if ( $this->library && !isset( $this->library->$finder ) ) {
			$this->dieWithError(
				[ "apierror-formcompletions-invalid-finder", $finder ]
			);
		}

		$this->completer = $this->getCompleter( $finder );
		return true;
	}

	public function loadPage( $page ) {
		$title = Title::newFromText( $page );
		if ( !$title ) {
			$this->dieWithError( [ "apierror-invalidtitle", $page ] );
		}

		$wikiPage = WikiPage::factory( $title );
		if ( !$wikiPage->exists() ) {
			$this->dieWithError( [ "apierror-missingtitle-byname", $title ] );
		}

		$content = $wikiPage->getContent();
		if ( !$content ) {
			$this->dieWithError(
				[ "apierror-missingcontent-pageid", $wikiPage->getID() ]
			);
		}
		return $content;
	}

	private function getCompleter( $finder ) {
		$handler = $this->library->$finder;
		if ( !isset( $handler->type ) ) {
			$completer = WPCompleter::getInstance( $this );
			$arg = $handler;
		} else {
			$completer = CompletionHandler::getCompleter( $this, $handler->type );
			$arg = $handler->arg;
		}
		$completer->setArg( $arg );
		return $completer;
	}

	private function handleCompletion() {
		$this->retValue = $this->getCompleter(
			$this->finder
		)->handleCompletion( $this->substr );
	}

	private function returnResults() {
		if ( $this->retValue ) {
			$result = $this->getResult();
			$result->addValue( null, 'pfcomplete', $this->retValue );
		}
	}

	protected function getAllowedParams() {
		return [
			'limit' => [
				ApiBase::PARAM_TYPE => 'limit',
				ApiBase::PARAM_DFLT => 10,
				ApiBase::PARAM_MIN => 1,
				ApiBase::PARAM_MAX => ApiBase::LIMIT_BIG1,
				ApiBase::PARAM_MAX2 => ApiBase::LIMIT_BIG2
			],
			'substr' => null,
			'finder' => null,
		];
	}

	protected function getParamDescription() {
		return [
			'substr' =>
			wfMessage( "formcompletions-api-substr-param" )->plain(),
			'finder' =>
			wfMessage( "formcompletions-api-finder-param" )->plain()
		];
	}

	protected function getDescription() {
		return wfMessage( "formcompletions-desc" )->plain();
	}

	protected function getExamples() {
		return [
			"api.php?action=fcautocomplete&substr=joe&finder=hydroGroup"
		];
	}
}
