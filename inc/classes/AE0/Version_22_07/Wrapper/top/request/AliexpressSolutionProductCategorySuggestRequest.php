<?php
/**
 * TOP API: aliexpress.solution.product.category.suggest request
 * 
 * @author auto create
 * @since 1.0, 2022.02.18
 */
class AliexpressSolutionProductCategorySuggestRequest
{
	/** 
	 * product main image
	 **/
	private $imageUrl;
	
	/** 
	 * language
	 **/
	private $language;
	
	/** 
	 * product title
	 **/
	private $title;
	
	private $apiParas = array();
	
	public function setImageUrl($imageUrl)
	{
		$this->imageUrl = $imageUrl;
		$this->apiParas["image_url"] = $imageUrl;
	}

	public function getImageUrl()
	{
		return $this->imageUrl;
	}

	public function setLanguage($language)
	{
		$this->language = $language;
		$this->apiParas["language"] = $language;
	}

	public function getLanguage()
	{
		return $this->language;
	}

	public function setTitle($title)
	{
		$this->title = $title;
		$this->apiParas["title"] = $title;
	}

	public function getTitle()
	{
		return $this->title;
	}

	public function getApiMethodName()
	{
		return "aliexpress.solution.product.category.suggest";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->language,"language");
		RequestCheckUtil::checkNotNull($this->title,"title");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
