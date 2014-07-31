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

namespace Togu\FrontendBundle\Data;

use Togu\AnnotationBundle\Data\AnnotationProcessor;
use Sonata\MediaBundle\Model\MediaManagerInterface;
use Sonata\MediaBundle\Provider\Pool;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class Formatter {
	private $processor;
	private $mediaManager;
	private $mediaPool;
	private $urlGenerator;

	/**
	 *
	 * @param AnnotationProcessor $processor
	 * @param UrlGeneratorInterface $urlGenerator
	 */
	public function __construct(AnnotationProcessor $processor, UrlGeneratorInterface $urlGenerator) {
		$this->processor = $processor;
		$this->urlGenerator = $urlGenerator;
	}

	protected function resolveLinks(&$links) {
		foreach ($links as $id => $value) {
			$links[$id] = $this->urlGenerator->generate($value);
		}
	}

	/**
	 * Prepares the data to be serialized
	 *
	 * @param mixed $entity The entity to serialize
	 *
	 * @return string The serialized data
	 */
	public function format($entities) {
		$data = array(
			'models' => array(),
			'images' => array(),
			'links' => array()
		);

		if(! is_array($entities)) {
			$entities = array($entities->getId() => $entities);
		}

		foreach ($entities as $entity) {
			if($entity instanceof \Application\Togu\ApplicationModelsBundle\Document\Page) {
				foreach ($entity->getAllSections() as $section) {
					$entities[$section->getId()] = $section;
				}
			}
		}

		foreach ($entities as $entity) {
			if($entity instanceof \Application\Togu\ApplicationModelsBundle\Document\Page) {
				$data['links'][$entity->getId()] = $entity;
			}

			$this->processor->getAllObjects($entity, $data['models']);
			$this->processor->getFieldValuesOfType($entity, 'image', $data['images']);
			$this->processor->getFieldValuesOfType($entity, 'link', $data['links']);

			if($entity instanceof \Application\Togu\ApplicationModelsBundle\Document\Page) {
				$data['links'][$entity->getId()] = $entity;
			}
		}

		array_merge($data['models'], $data['links']);

		$this->resolveLinks($data['links']);

		return $data;
	}
}