<?php
////////////////////////////////////////
//Create images from svg template//////
//////////////////////////////////////
$options=[];

/* default options values */
//$options["data_file"]="data.csv";
//$options["svg"]="template.svg";
//$options["result_folder"]="result";
//$options["columns_for_names"]=[];
//$options["columns_for_base64encode"]=[];
//$options["result_extension"]=".png";

$img = new ImgCreate($options);

$img->empty_result_folder();

$img->read_file(";");

$img->read_template_svg();

$img->create_svg();

$img->create_image();

$img->delete_svg();

Class ImgCreate
{
	protected $options=[
	"data_file"=>"data.csv",
	"svg"=>"template.svg",
	"result_folder"=>"result",
	"columns_for_names"=>[],
	"columns_for_base64encode"=>[],
	"result_extension"=>".png"
	];


  public function __construct($options)
	{
		$this->options=$options+$this->options;

		try	{
			if (!file_exists($this->options["data_file"])) {
					throw new Exception("File '{$this->options["data_file"]}' doesn't exist");
			 }
		}
		catch(Exception $e){
			echo $e->getMessage(), "\n";
			exit;
		}

		if (!file_exists($this->options["result_folder"])){
			mkdir($this->options["result_folder"]);
		}

	}


	public function read_file($delimiter)
	{
		$this->data=file($this->options["data_file"], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

		$this->search=explode($delimiter,trim($this->data[0]));

		array_shift($this->data);

		$this->count_data=count($this->data);

		for($i=0; $i<$this->count_data; $i++){

			$this->replace[$i]=explode($delimiter,trim($this->data[$i]));
		}

		if ($this->options["columns_for_base64encode"]){
			$this->base64encode();
		}
	}


	public function empty_result_folder()
	{
		$this->files = glob("./".$this->options["result_folder"]."/*");

		foreach($this->files as $file){

			unlink($file);
		}
	}


	public function base64encode()
	{
		array_walk($this->replace, function(&$replace, $key, $columns_for_base64encode){

			foreach($columns_for_base64encode as $column){

				$tmp=explode(".", $replace[$column]);

				$tmp_extension=end($tmp);

				$extension=preg_replace('~jpe?g~i',"jpeg",$tmp_extension);

				$encoded_image=base64_encode(file_get_contents(trim($replace[$column])));

				$replace[$column]="data:image/".$extension.";base64,".$encoded_image;
			}
		}, $this->options["columns_for_base64encode"]);
	}


	public function create_files_name()
	{
		$this->names=[];

		array_map(function($replace, $key){

			$this->names[]="./".$this->options['result_folder']."/".$key."__".implode("_",array_filter($replace, function($key){
				if(in_array($key, $this->options["columns_for_names"])) return true;},ARRAY_FILTER_USE_KEY));

		},$this->replace, array_keys($this->replace));

			$this->sanitize_file_name();
	}


	public function sanitize_file_name()
	{
		$this->names=array_map(function($file_name){
			return preg_replace("~[^a-zA-Z0-9\._\/]~","",$file_name);
		},$this->names);
	}


	public function read_template_svg()
	{
		$this->template_svg_data=$this->data_from_svg_template=file_get_contents($this->options["svg"]);

		$this->create_files_name();
	}


	public function create_svg()
	{
		foreach($this->names as $name_index=>&$name){

			$modified_svg_data=str_replace($this->search, $this->replace[$name_index], $this->template_svg_data);

			if (php_sapi_name() == "cli"){
				 echo $name_index.") ".$name."\n";
   		}
			else {
				echo $modified_svg_data;
			}

			file_put_contents($name.".svg", $modified_svg_data);
		}

		$this->svg_files = glob("./".$this->options["result_folder"]."/*.svg");
	}


	public function create_image()
	{
		try{

			if (!class_exists('Imagick')){

				throw new Exception ("Class 'Imagick' not found \n");
			}

			$im = new Imagick();

		}catch (Exception $e) {
			echo $e->getMessage(), "\n";
			return 0;
		}

		foreach($this->svg_files as $svg_file){

			$file_name=pathinfo($svg_file);

			$im->readImage($svg_file);

			$im->writeImage($this->options['result_folder']."/{$file_name['filename']}".$this->options['result_extension']);

			$im->clear();
		}
	}


	public function delete_svg()
	{
		foreach($this->svg_files as $svg_file){
			unlink($svg_file);
		}
	}
}