<?php

/*
 * Copyright (c) 2012-2014 Alessandro Siragusa <alessandro@togu.io>
 *
 * This file is part of the Togu CMS.
 *
 * Togu is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Togu is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Togu.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Togu\FrontendBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use JMS\Serializer\SerializationContext;
use Togu\FrontendBundle\Data\Formatter;
use Sonata\MediaBundle\Model\MediaManagerInterface;
use FOS\RestBundle\View\View;


/**
 * ApiController gives basic website informations
 *
 * @author asiragusa
 *
 */
class ApiController extends FOSRestController {
	private $mediaManager;
	private $formatter;

	/**
	 *
	 * @param Formatter $formatter
	 */
	public function setFormatter(Formatter $formatter) {
		$this->formatter = $formatter;
	}

	/**
	 *
	 * @param MediaManagerInterface $mediaManager
	 */
	public function setMediaManager(MediaManagerInterface $mediaManager) {
		$this->mediaManager = $mediaManager;
	}

	/**
	 *
	 * @param Request $request
	 * @throws NotFoundHttpException
	 */
	public function imageAction(Request $request) {
		$id = $request->query->get('id');
		if(! $id) {
			throw new NotFoundHttpException('Argument id is mandatory');
		}
		$media = $this->mediaManager->find($id);
		if(! $media) {
			throw new NotFoundHttpException(sprintf('Media %s could not be found', $media));
		}
        $context = $this->getSerializerContext(array('list'));
        $view = View::create(array(
        	"media" => array($media)
        ), 200)->setSerializationContext($context);
        return $this->handleView($view);
	}

	protected function getSerializerContext($groups = array(), $version = null) {
		$serializeContext = SerializationContext::create();
		$serializeContext->enableMaxDepthChecks();
		$serializeContext->setGroups(array_merge(
				array(\JMS\Serializer\Exclusion\GroupsExclusionStrategy::DEFAULT_GROUP),
				$groups
		));
		if ($version !== null) $serializeContext->setVersion($version);
		return $serializeContext;
	}

}