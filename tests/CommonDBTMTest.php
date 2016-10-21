<?php
/*
-------------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
Copyright (C) 2015-2016 Teclib'.

http://glpi-project.org

based on GLPI - Gestionnaire Libre de Parc Informatique
Copyright (C) 2003-2014 by the INDEPNET Development Team.

-------------------------------------------------------------------------

LICENSE

This file is part of GLPI.

GLPI is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

GLPI is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with GLPI. If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------
*/

/* Test for inc/computer_softwareversion.class.php */

class CommonDBTMTest extends DbTestCase {

   public function testAdd() {
      $input = [
         'name'        => self::getUniqueString(),
         '_no_history' => false
      ];

      $mock = $this->getMockBuilder('UserTitle')
         ->enableProxyingToOriginalMethods()
         ->setMethods(['prepareInputForAdd', 'post_addItem'])
         ->getMock();
      /*
      $mock->expects($this->once())
         ->method('prepareInputForAdd')
         ->with($input)
         ->will($this->returnValue($input));
      */
      $mock->expects($this->once())
         ->method('post_addItem')
         ->with()
         ->will($this->returnValue(NULL));

      $id = $mock->add($input);
   }
}
