<?php

namespace AppBundle\Tests\Entity;

use AppBundle\Entity\Company;


class CompanyEntityUnitTest extends \PHPUnit_Framework_TestCase {
    
	// Check whether the setPassword function is working correctly
	public function testKeyKnowledges(){
		
		// new entity
		$a = new Company();
		
		$keyKnowledges = array("water engineering","energy","rain gardening","spray ponds","systems administration","multifunctional playground", "green roof");
		
		// Use the setPassword method 
		$a->setKeyKnowledges($keyKnowledges);
		
		// Assertions
        $this->assertContains("energy", $a->getKeyKnowledges());
        $this->assertContains("water engineering", $a->getKeyKnowledges());
        $this->assertNotEmpty($a->getKeyKnowledges());

	}
	// Check whether the setTlf function is working correctly
	public function testSetTlf(){
		
		// new entity
		$a = new Company();
		
		// Use the setPhone method 
		$a->setTlf("12312312");
		
		// Assert the result 
		$this->assertEquals("12312312", $a->getTlf());
		
	}
    // Check whether the setCompetence function is working correctly
    public function testSetCompetence(){

        // new entity
        $a = new Company();
        $competence = 'This actor completely incompetent';

        // Use the setPhone method
        $a->setCompetence($competence);

        // Assert the result
        $this->assertEquals('This actor completely incompetent', $a->getCompetence());

    }
    // Check whether the setEmail function is working correctly
    public function testSetEmail(){

        // new entity
        $user = new Company();

        // Use the setEmail method
        $user->setEmail("per@mail.com");

        // Assert the result
        $this->assertEquals("per@mail.com", $user->getEmail());

    }
    // Check whether the setField function is working correctly
    public function testSetField(){

        // new entity
        $a = new Company();

        // Use the setEmail method
        $a->setField("engineering");

        // Assert the result
        $this->assertEquals("engineering", $a->getField());
    }
    // Check whether the setLocation function is working correctly
    public function testSetLocation(){

        // new entity
        $a = new Company();

        // Use the setEmail method
        $a->setLocation("Elvebakk, Åfjord");

        // Assert the result
        $this->assertEquals("Elvebakk, Åfjord", $a->getLocation());
    }

    // Here follows company specific tests...
    public function testSetType(){

        // new entity
        $a = new Company();

        // Use the setEmail method
        $a->setType("test");

        // Assert the result
        $this->assertEquals("test", $a->getType());
    }
    // Check whether the setName function is working correctly
    public function testSetName(){

        // new entity
        $p = new Company();
        $name = 'This Project completely incompetent';

        // Use the setPhone method
        $p->setName($name);

        // Assert the result
        $this->assertEquals('This Project completely incompetent', $p->getName());
    }
    // Check whether the setOrgNo function is working correctly
    public function testSetOrgNo(){

        // new entity
        $p = new Company();
        $no = '911976575';

        // Use the setPhone method
        $p->setOrgNr($no);

        // Assert the result
        $this->assertEquals('911976575', $p->getOrgNr());
    }

}