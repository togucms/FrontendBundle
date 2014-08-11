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

class Formatter {
	private $processor;

	/**
	 *
	 * @param AnnotationProcessor $processor
	 * @param UrlGeneratorInterface $urlGenerator
	 */
	public function __construct(AnnotationProcessor $processor) {
		$this->processor = $processor;
	}

	/**
	 *
	 * @param array $links
	 */
	protected function convertLinks(array &$links) {
		foreach ($links as $id => $value) {
			$links[$id] = true;
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
			$this->processor->getAllObjects($entity, $data['models']);
			$this->processor->getFieldValuesOfType($entity, 'image', $data['images']);
			$this->processor->getFieldValuesOfType($entity, 'link', $data['links']);
		}

		$this->convertLinks($data['links']);

		return $data;
	}
}