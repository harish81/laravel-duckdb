<?php

namespace Harish\LaravelDuckdb\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Harish\LaravelDuckdb\Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Factories\Factory;

class PersonFactory extends Factory{

    protected $model = Person::class;
    public function definition()
    {
        return [
            'name' => fake()->name(),
            'age' => fake()->numberBetween(13, 50),
            'rank' => fake()->numberBetween(1, 10),
            'salary' => fake()->randomFloat(null, 10000, 90000)
        ];
    }
}
class Person extends \Harish\LaravelDuckdb\LaravelDuckdbModel{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    protected $connection = 'my_duckdb';
    protected $table = 'people';
    protected $guarded = ['id'];

    protected static function newFactory()
    {
        return PersonFactory::new();
    }
}
class DuckDBSchemaStatementTest extends TestCase
{
    public function test_migration(){

        Schema::connection('my_duckdb')->dropIfExists('people');
        DB::connection('my_duckdb')->statement('DROP SEQUENCE IF EXISTS people_sequence');

        DB::connection('my_duckdb')->statement('CREATE SEQUENCE people_sequence');
        Schema::connection('my_duckdb')->create('people', function (Blueprint $table) {
            $table->id()->default(new \Illuminate\Database\Query\Expression("nextval('people_sequence')"));
            $table->string('name');
            $table->integer('age');
            $table->integer('rank');
            $table->unsignedDecimal('salary')->nullable();
            $table->timestamps();
        });

        $this->assertTrue(Schema::hasTable('people'));
    }


    public function test_model(){
        //truncate
        Person::truncate();

        //create
        $singlePerson = Person::factory()->make()->toArray();
        $newPerson = Person::create($singlePerson);

        //batch insert
        $manyPersons = Person::factory()->count(10)->make()->toArray();
        Person::insert($manyPersons);

        //update
        $personToUpdate = Person::where('id', $newPerson->id)->first();
        $personToUpdate->name = 'Harish81';
        $personToUpdate->save();
        $this->assertSame(Person::where('name', 'Harish81')->count(), 1);

        //delete
        Person::where('name', 'Harish81')->delete();

        //assertion count
        $this->assertCount( 10, Person::all());
    }
}