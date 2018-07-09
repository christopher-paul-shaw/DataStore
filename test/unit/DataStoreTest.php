<?php
namespace App\Test;
use App\DataStore;
use PHPUnit\Framework\TestCase;
use Gt\Core\Path;

class DataStoreTest extends TestCase {

    public function tearDown () {
        $path = Path::get(Path::DATA)."/default/";
        $this->removeDirectory($path);
    }

    public function testICanCreateEntity() {
        $payload = [
           'id_entity' => 'testId',
           'test' => 'case'
        ];
              
        $setup_entity = new DataStore('exampleID');
        $setup_entity->create($payload);
        
        $e = new DataStore('exampleID');      
        $this->assertEquals($payload['test'], $e->getValue('test'));    
    }

    public function testICanDeleteEntity() {
 
        $payload = [
           'id_entity' => 'testId1',
           'test' => 'case'
        ];
              
        $setup_entity = new DataStore('exampleID');
        $setup_entity->create($payload);
     
        $e = new DataStore('exampleID');
        $e->delete();
        
        $e = new DataStore('exampleID'); 
        $this->assertEquals(false, $e->getValue('test'));
      
    }   

    public function testICantUseUnsafeFields () {
        $payload = [
           '../id_entity' => 'testId1',
           './test' => 'case'
        ];
              
        $setup_entity = new DataStore('exampleID');
        $this->expectExceptionMessage('Invalid Field');
        $setup_entity->create($payload);
    }

     public function testICanSearch () {
        $payload = [
           '../id_entity' => 'testId1',
           './test' => 'case'
        ];
              
        $e1 = new DataStore('exampleID');
        $e1->create(['test' => 1, 'foo' => 'bar']);
        $e2 = new DataStore('exampleID2');
        $e2->create(['test' => 2, 'foo' => 'bar']);
        $e3 = new DataStore('exampleID3');
        $e3->create(['test' => 3, 'foo' => 'bar']);
 
        $search = new DataStore();
        $results = $search->search();

        $this->assertTrue(count($results) > 0);

    }
       
    public function testICantDuplicateEntity() {
    
        $payload = [
           'id_entity' => 'testId3',
           'test' => 'case'
        ];
              
        $n = new DataStore('exampleID');
        $n->create($payload);
        
        $this->expectExceptionMessage('Entity Already Exists');
        $e = new DataStore('exampleID');
        $e->create($payload);  
    }   
    
    public function testICanUpdateEntity() {    
        $payload = [
           'id_entity' => 'testId',
           'test' => 'case'
        ];
         
        $string = 'something';    
        $e = new DataStore('exampleID');
        $e->create($payload);
        $e->setValue('test', $string);      
        $this->assertEquals($string, $e->getValue('test'));  
    }


    public function testICanPayloadUpdateEntity() {    
        $payload = [
           'id_entity' => 'testId',
           'test' => 'case'
        ];
         
        $string = 'something';    
        $e = new DataStore('exampleID');
        $e->create($payload);

    	$new_payload = [
    		'test' => $string,
    		'id_entity' => 'new'
    	];

        $e->update($new_payload);      
        $this->assertEquals($string, $e->getValue('test'));  
    }

    public function testICanSearchFilter() {


        $e = new DataStore('example1');
        $e->create(['name' => 'foo']);


        $e = new DataStore('example2');
        $e->create(['name' => 'bar']);


        $e = new DataStore('example3');
        $e->create(['name' => 'foobar']);



        $e = new DataStore();
        $e->setType('default');


        // TODO - Test Cant Access Files during Search.
        $results = $e->search();
        print_r($results);
        $results = $e->search(
            ['name','!=','foo']
        );
        print_r($results);
        
        /*
        $this->assertTrue(count($results) > 0);

        $results = $e->search(
            ['name','like','foo']
        );

        $this->assertTrue(count($results) == 2);

        $results = $e->search(
            ['name','=','none']
        );

        $this->assertTrue(count($results) == 0);

    */


    }

    public function removeDirectory($path) {
        $files = glob($path . '/*');
        foreach ($files as $file) {
            is_dir($file) ? $this->removeDirectory($file) : unlink($file);
        }
        rmdir($path);
    }
}



