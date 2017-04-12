# **Rumble**

A php migration tool for AWS dynamoDB.

### **Requirements**
To use ```rumble``` for migration and seeding, you should either have aws dymanodb locally installed or have a valid aws credential for the remote version in a particular aws region.

### **Disclosure**
```rumble``` is far from being complete compared to other migration tool out there. More features will be added in the future.

### **Naming Convention**
Since ```rumble``` is still in its infancy, it cannot generate migration or seeder file automatically. You have to manually create files for both migrations and seeds. Migration and Seed files are to be placed in ```migrations``` and ```seeds``` directories at the root of your project.

A migration or seed file must be named with an underscore ```(_)``` separating each word that makes up the file name. e.g:
```creat_app_records_table.php```, is a valid file name for a migration or a seed file.

While the file name uses underscore ```(_)``` naming style, the class name for the ```creat_app_records_table.php``` file uses PasCal naming style. i.e the first letter of every word that makes up the file name must be capitalized e.g: ```CreateAppRecordsTable```.

### **Class Definition**
- **Migration:** Every migration file (class) you create must extend the rumble ```Migration``` class and must define a ```up``` method.
- **Seed:** Every seed file (class) you create must extend the rumble ```Seeder``` class and must define a ```seed``` method.

### **Using Rumble**
- **Migration:** to migrate files, run ```rumble migrate``` from the root directory of your project. e.g: ```vendor/bin/rumble migrate```
- **Seed:** to seed files, run ``` rumble seed``` from the root directory of your project e.g ```vendor/bin/rumble seed```

### **Supported DynamoDb Features**
Currently, ```rumble``` supports only the below dynamodb features;
- Create table
- Update table
- Delete table
- Add Item
- Batch Write Item

### **Tutorial - Create a new table**
The below code sample shows the minimum required params to create a dynamodb table using ```rumble```. This is from the ```up``` method of the migration file (```create_app_records_table.php```).
The ```up``` method, is the only required method to be implemented by every migration file you create.
```php
<?php
use Rumble/Migration;

class CreateAppRecordsTable extends Migration
{
    public funtion up()
    {
        $table = $this->table('app_records'); //table name
        $table->addAttribute('app_uid', 'S'); //primary key data type - String(S)
        $table->addHash('app_uid');  //primary key
        $table->setWCU(10); //Write Capacity Unit (Provisioned write throughPut)
        $table->setRCU(10); //Read Capacity Unit (Provisioned read throughPut)
        $table->create();  //create table
    }
}
```

### **Tutorial - Seed table**
The below sample code shows the minimum required params to seed a dynamodb table (sample created above). This is from the ```seed``` method of the seed file (```app_records_seeder.php```).
The ```seed``` method is the only required method to be implemented by every seed file you create

```php
<?php
use Rumble/Seeder;

class AppRecordsTableSeeder extends Seeder 
{
    public function seed()
    {
        $table = $this->table('app_records');
        $table->addItem(['app_uid' => 'x435-n956-00jX-u2fX', 'uninstall' => ['reason' => 'Still thinking of one.']);
        $table->addItem(['app_uid' => '944-jjU0-o0Hi-y4hh4', 'events' => ['action' => 'click', 'date' => '2017-04-10']]); 
        $table->save();
    }
}

```

### **Database Configuration**
```rumble``` uses a ```rumble.yml``` file as its configuration file. You have to create this file at the root of your project. Use the ```rumble.sample.yml`` file as a guide.