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

namespace Togu\FrontendBundle\DataFixtures\PHPCR;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Config\FileLocator;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Symfony\Component\Yaml\Yaml;

use Application\Togu\ApplicationModelsBundle\Document\Page;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Phpcr\Route;

class DefaultPages extends ContainerAware implements FixtureInterface, OrderedFixtureInterface
{
	protected $manager;
	protected $modelLoader;
	protected $mediaManager;
	protected $rootData;
	protected $sectionConfigs = array();
	protected $links = array();

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {

        $this->manager = $manager;
        $this->rootData = $manager->find(null, '/data');

    	$this->modelLoader = $this->container->get('togu.generator.model.config');
        $configDir = $this->container->getParameter('togu.generator.config.dir');
        $locator = new FileLocator($configDir . '/fixtures');
        $fixtures = Yaml::parse(file_get_contents($locator->locate("defaultpages.yaml")));
        $this->mediaManager = $this->container->get('sonata.media.manager.media');

        $rootModelClass = $this->modelLoader->getFullClassName($fixtures['rootModel']['type']);
        $rootModel = new $rootModelClass();
        $rootModel->setParentDocument($this->rootData);

        $sectionConfig = $rootModel->getSectionConfig();
        $sectionConfig->setText($fixtures['rootModel']['_section']['title']);
        $sectionConfig->setLeaf(false);
        $sectionConfig->setType('rootModel');

		$manager->persist($rootModel);

        foreach($fixtures['rootModel']['nextSection'] as $section) {
	        $this->createModel($section, $rootModel);
		}

        $manager->flush();
		foreach ($this->links as $link) {
			$section = $this->sectionConfigs[$link['value']];
			$this->setValue($link['instance'], $link['fieldName'], $section->getPage());
		}

        $manager->flush();
    }

    protected function createModel($config, $parent = null) {
		$type = $config['type'];
		unset($config['type']);

		$className = $this->modelLoader->getFullClassName($type);

		$instance = new $className();

		foreach ($config as $fieldName => $value) {
			$fieldType = $this->getFieldType($type, $fieldName);
			switch ($fieldType) {
				case "image":
					$this->setValue($instance, $fieldName, $this->mediaManager->find($value));
					break;
				case "link":
					$this->links[] = array(
						"instance" => $instance,
						"fieldName" => $fieldName,
						"value" => $value
					);
					break;
				case "reference":
					foreach ($value as $child) {
						$this->setValue($instance, $fieldName, $this->createModel($child), true);
					}
					break;
				case "nextSection":
					foreach ($value as $child) {
						$this->createModel($child, $instance);
					}
					break;
				case "_page":
					$page = $instance->getSectionConfig()->getPage(true);
					$page->setTitle($config['_page']['title']);
					break;
				case "_section":
					$sectionConfig = $instance->getSectionConfig();
					$sectionConfig->setText($config['_section']['title']);
					$sectionConfig->setLeaf($config['_section']['leaf']);
					$sectionConfig->setType($type);
					if($sectionConfig->getLeaf() === true) {
						$this->sectionConfigs[$sectionConfig->getText()] = $sectionConfig;
					}
					break;
				default:
					$this->setValue($instance, $fieldName, $value);
			}
		}

		$instance->setParentDocument($this->rootData);
		$this->manager->persist($instance);

		if($parent !== null) {
			$instance->getSectionConfig()->setParentSection($parent);
		}

		return $instance;
    }

    protected function getFieldType($modelName, $fieldName) {
    	if($fieldName == "nextSection" || $fieldName == "_page" || $fieldName == "_section") {
    		return $fieldName;
    	}
    	$config = $this->modelLoader->getConfig($modelName);
    	while(! isset($config['fields'][$fieldName]) && isset($config['extends'])) {
    		$config = $this->modelLoader->getConfig($config['extends']);
    	}
    	return $config['fields'][$fieldName]['model']['type'];
    }

    protected function setValue($instance, $fieldName, $value, $isReference = false) {
    	$instance->{$this->setterName($fieldName, $isReference)}($value);
    }

    protected function setterName($fieldName, $isReference) {
    	$setter = "set";
    	if($isReference) {
    		$setter = "add";
    	}
    	return $setter . ucfirst($fieldName);
    }

    public function getOrder()
    {
    	return 2;
    }
}
