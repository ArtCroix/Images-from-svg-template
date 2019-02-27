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
//$options["data_file_encoding"]="Windows-1251";
//$options["result_image_width"]=0;
//$options["result_image_height"]=0;
//$options["save_proportion"]=true;
//$options["x_res"]=300;
//$options["y_res"]=300;

$img = new ImgCreate($options);

$img->empty_result_folder();

$img->encode_file_to_utf8();

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
	"result_extension"=>".png",
	"data_file_encoding"=>"Windows-1251",
	"x_res"=>300,
	"y_res"=>300,
	"result_image_width"=>0,
	"result_image_height"=>0,
	"save_proportion"=>true
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

	public function encode_file_to_utf8()
	{
		$handle=fopen("temp_data.csv", "w+");  

		$unencoded_data=file($this->options["data_file"]);

		array_map(function($unencoded_string) use ($handle){
			
			$encoded_string=mb_convert_encoding($unencoded_string, "utf-8", $this->options["data_file_encoding"]); 

			fwrite($handle, $encoded_string); },$unencoded_data);
			
		fclose($handle);
		$this->options["data_file"]="temp_data.csv";
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
		
		unlink($this->options["data_file"]);
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
			
			preg_replace("~[^a-zA-Z0-9\._\/]~","",$file_name);
			
			return $this->translit($file_name);
			
		},$this->names);
	}

	public function translit($str)
	{
		$rus = array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я');
		$lat = array('A', 'B', 'V', 'G', 'D', 'E', 'E', 'Zh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Ch', 'Sh', 'Sch', 'Y', 'Y', 'Y', 'E', 'Yu', 'Ya', 'a', 'b', 'v', 'g', 'd', 'e', 'e', 'zh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sch', 'y', 'y', 'y', 'e', 'yu', 'ya');
		return str_replace($rus, $lat, $str);
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
			$im->setResolution($this->options["x_res"], $this->options["y_res"]);
		}catch (Exception $e) {
			echo $e->getMessage(), "\n";
			return 0;
		}
		
				
		foreach($this->svg_files as $svg_file){

			$file_name=pathinfo($svg_file);
			
			$im->readImage($svg_file);
			
 			if($this->options["result_image_width"]!=0){
				
				$im->scaleImage($this->options["result_image_width"], $this->options["result_image_height"], $this->options["save_proportion"]);
			} 
	
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