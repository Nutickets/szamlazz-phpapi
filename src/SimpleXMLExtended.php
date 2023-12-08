<?php

namespace SzamlaAgent;

use SimpleXMLElement;

class SimpleXMLExtended extends SimpleXMLElement
{
    public function addCDataToNode(SimpleXMLElement $node, $value = '')
    {
        if ($domElement = dom_import_simplexml($node)) {
            $domOwner = $domElement->ownerDocument;
            $domElement->appendChild($domOwner->createCDATASection($value));
        }
    }

    public function addChildWithCData($name = '', $value = '')
    {
        $newChild = parent::addChild($name);

        if (SzamlaAgentUtil::isNotBlank($value)) {
            $this->addCDataToNode($newChild, $value);
        }

        return $newChild;
    }

    public function addCData($value = '')
    {
        $this->addCDataToNode($this, $value);
    }

    public function extend($add)
    {
        if ($add->count() != 0) {
            $new = $this->addChild($add->getName());
        } else {
            $new = $this->addChild($add->getName(), $this->cleanXMLNode($add));
        }

        foreach ($add->attributes() as $a => $b) {
            $new->addAttribute($a, $b);
        }

        if ($add->count() != 0) {
            foreach ($add->children() as $child) {
                $new->extend($child);
            }
        }
    }

    public function cleanXMLNode($data)
    {
        $xmlString = $data->asXML();
        if (str_contains($xmlString, '&')) {
            $cleanedXmlString = str_replace('&', '&amp;', $xmlString);
            $data = simplexml_load_string($cleanedXmlString);
        }

        return $data;
    }

    public function remove(): static
    {
        $node = dom_import_simplexml($this);
        $node->parentNode->removeChild($node);

        return $this;
    }

    public function removeChild(SimpleXMLElement $child): static
    {
        $node = dom_import_simplexml($this);
        $child = dom_import_simplexml($child);
        $node->removeChild($child);

        return $this;
    }
}
