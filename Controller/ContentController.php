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

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Cmf\Bundle\ContentBundle\Controller\ContentController as BaseContentController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Togu\FrontendBundle\Data\Formatter;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use JMS\Serializer\SerializationContext;
use Application\Togu\ApplicationModelsBundle\Document\Page;

/**
 * Special routes to demo the features of the Doctrine Router in the CmfRoutingBundle
 */
class ContentController extends BaseContentController
{
	protected $container;
	protected $formatter;

	/**
	 * Setter for the container
	 *
	 * @param ContainerInterface $container
	 */
	public function setContainer(ContainerInterface $container) {
		$this->container = $container;
	}

	public function setFormatter(Formatter $formatter) {
		$this->formatter = $formatter;
	}

    /**
     * This action is mapped in the controller_by_type map
     *
     * This can inject something else for the template for content with this type
     *
     * @param object $contentDocument
     *
     * @return Response
     */
    public function typeAction($contentDocument, $_format, Request $request)
    {
        if (!$contentDocument || ! $contentDocument instanceof Page) {
            throw new NotFoundHttpException('Content not found');
        }
        if ($this->container->has('profiler')) {
        	$this->container->get('profiler')->disable();
        }
        $isAdminUi = $request->query->get('admin', false) && $this->container->get('security.context')->isGranted('ROLE_ADMIN');

        $data = $params = $this->formatter->format($contentDocument);
		if("html" == $_format) {
	        $params = array(
	            'cmfMainContent' => $contentDocument,
	        	'jsData' => $data,
	        	'settings' => array("currentPath"=>"/","urlBase"=>"/"),
	        	'isAdminUI' => $isAdminUi,
	        	'serializationContext' => $this->getSerializerContext(array('front'))
	        );
		}

        return $this->renderResponse('ApplicationToguApplicationModelsBundle:Default:index.html.twig', $params);
    }

    protected function getView($params) {
    	$view = parent::getView($params);
    	$view->setSerializationContext($this->getSerializerContext(array('front')));
    	return $view;
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