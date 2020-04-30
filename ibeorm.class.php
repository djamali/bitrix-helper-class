<?php
//12.12.2019

class IBEORM{
  private $ID = false;
  private $IB = false;
  private $fields = false;
  private $props = false;
  private $fields_change = [];
  private $props_change = [];
  private $has_changes = false;
  private $btnarr = false;
  
  protected function __construct($fields,$props){
    $this->IB = $fields['IBLOCK_ID'];
    $this->ID = $fields['ID'];
    $this->fields = $fields;
    $this->props = $props;
  }
  
  public function getID(): int{
    return $this->ID;
  }
  
  public function getIB(): int{
    return $this->IB;
  }
  
  public function getIBArr(): array{
    return self::getIBArrByID($this->IB);
  }
  
  public function getSiteArr(): array{
    if(($ib = $this->getIBArr()) && ($res = CSite::GetByID($ib['LID'])) && ($site = $res->Fetch())){
      return $site;
    }
    return [];
  }
  
  public function getIBType(): string{
    return $this->getIBArr()['IBLOCK_TYPE_ID'];
  }
  
  public function getAdminPageEditLink(){
    return '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID='.$this->getIB().'&type='.$this->getIBType().'&lang=ru&find_section_section=-1&ID='.$this->getID();
  }
  
  //TODO
  public function getIBFields(){
    if(CModule::includeModule('iblock') && ($res = CIBlock::GetByID($this->IB)) && ($ar_res = $res->GetNext())){
      return $ar_res;
    }
    return false;
  }
  
  public function inSection(): bool{
    return intval($this->getField('IBLOCK_SECTION_ID', 0)) > 0;
  }
  
  public function getSectionID(): int{
    return $this->getField('IBLOCK_SECTION_ID', 0);
  }
  
  public function getSection(): IBSORM{
    return IBSORM::getByID($this->IB, $this->getSectionID());
  }
  
  public function isActive(): bool{
    return $this->getField('ACTIVE') == 'Y';
  }
  
  public function setActive(bool $active=true): void{
    $this->setField('ACTIVE',$active?'Y':'N');
  }
  
  public function getName(): string{
    return $this->getField('NAME', '');
  }
  
  public function setName(string $name): void{
    $this->setField('NAME', $name);
  }
  
  public function getSort(): int{
    return $this->getField('SORT', 500);
  }
  
  public function setSort(int $sort): void{
    $this->setField('SORT',$sort);
  }
  
  public function getCode(): string{
    return $this->getField('CODE', '');
  }
  
  public function setCode(string $code): void{
    $this->setField('CODE', $code);
  }
  
  public function getExtCode(): string{
    return $this->getField('EXTERNAL_ID', '');
  }
  
  public function setExtCode(string $code): void{
    $this->setField('EXTERNAL_ID', $code);
  }
  
  public function getPreviewText(): string{
    return $this->getField('PREVIEW_TEXT', '');
  }
  
  public function getPreviewImg(): int{
    $out = $this->getField('PREVIEW_PICTURE', 0);
    //Если объект создан с помощью makeByFields, то результатом будет массив
    if(is_array($out))
      return $out['ID'];
    return $out;
  }
  
  public function hasPreviewImg(): bool{
    return $this->getPreviewImg() != 0;
  }
  
  public function getPreviewImgObj(): FileEx{
    return FileEx::getByID($this->getPreviewImg());
  }
  
  public function getPreviewImgSrc(): string{
    $data = $this->getField('PREVIEW_PICTURE', 0);
    if($data['SRC'])
      return $data['SRC'];
    return $this->getPreviewImgObj()->getPath();
  }
  
  public function getDetailImg(): int{
    $out = $this->getField('DETAIL_PICTURE', 0);
    //Если объект создан с помощью makeByFields, то результатом будет массив
    if(is_array($out))
      return $out['ID'];
    return $out;
  }
  
  public function hasDetailImg(): bool{
    return $this->getDetailImg() != 0;
  }
  
  public function getDetailImgObj(): FileEx{
    return FileEx::getByID($this->getDetailImg());
  }
  
  public function getDetailImgSrc(): string{
    $data = $this->getField('DETAIL_PICTURE', 0);
    if($data['SRC'])
      return $data['SRC'];
    return $this->getDetailImgObj()->getPath();
  }
  
  public function getDetailText(): string{
    return $this->getField('DETAIL_TEXT', '');
  }
  
  public function getField(string $name, $default=false){
    if(array_key_exists($name, $this->fields) && $this->fields[$name])
      return $this->fields[$name];
    return $default;
  }
  
  public function getProp(string $code, $target='VALUE', $default=false){
    $isarr = is_array($this->props[$code][$target]);
    if(array_key_exists($code, $this->props) && $this->props[$code][$target] !== false && ((!$isarr && $this->props[$code][$target]) || ($isarr && count($this->props[$code][$target]) > 0))){
        return $this->props[$code][$target];
    }
    return $default;
  }
  
  public function getPropFile(string $code, $target='VALUE', $default=false){
    $prop = $this->getProp($code,$target,$default);
    if($prop === $default)
      return $default;
    if(is_array($prop)){
      $out = [];
      foreach($prop as $e)
        if($r = FileEx::getByID($e))
          $out[] = $r;
      return $out;
    }elseif($r = FileEx::getByID($prop)){
      return $r;
    }
    return $default;
  }
  
  public function getProps(): array{
    return $this->props;
  }
  
  public function getFields(): array{
    return $this->fields;
  }
  
  public function getPropElement(string $code){
    if(($el_id = $this->getProp($code)) && ($el = self::getByID(0, $el_id))){
      return $el;
    }
    return false;
  }
  
  public function getPropElements(string $code): array{
    $out = [];
    if($el_ids = $this->getProp($code)) foreach($el_ids as $el_id) if($el = self::getByID(0, $el_id)){
      $out[$el->getID()] = $el;
    }
    return $out;
  }
  
  public function setField(string $name, $value){
    if(array_key_exists($name,$this->fields) && $this->fields[$name] === $value)
      return;
    $this->fields_change[$name] = $value;
    $this->has_changes = true;
  }
                      
  public function setProp(string $code, $value, $target='VALUE'){
    if(!array_key_exists($code,$this->props) || $this->props[$name][$target] === $value)
      return;
    if(!is_array($this->props_change[$code]))
      $this->props_change[$code] = [];
    $this->props_change[$code][$target] = $value;
    $this->has_changes = true;
  }
  
  public function hasProp(string $code): bool{
    return array_key_exists($code,$this->props);
  }
  
  public function emptyProp(string $code, $target='VALUE'): bool{
    if($this->props[$code][$target])
      return false;
    return true;
  }
  
  public function hasPropAndValue(string $code, $target='VALUE'): bool{
    return $this->hasProp($code) && !$this->emptyProp($code, $target);
  }
  
  public function getBtnArr(): array{
    if(!$this->btnarr)
      $this->btnarr = CIBlock::GetPanelButtons($this->IB, $this->ID, 0, array("SECTION_BUTTONS" => false, "SESSID" => false));
    return $this->btnarr;
  }
  
  public function getEditLink(){
    return $this->getBtnArr()["edit"]["edit_element"]["ACTION_URL"];
  }
  
  public function getDeleteLink(){
    return $this->getBtnArr()["edit"]["delete_element"]["ACTION_URL"];
  }
  
  public function getPageURL(): string{
    return $this->fields['DETAIL_PAGE_URL'];
  }
                      
  public function commit($byapi = true): bool{
    if(!CModule::includeModule('iblock') || !$this->has_changes)
      return false;
    
    $props_keys = ['VALUE','VALUE_TYPE','VALUE_ENUM_ID','DESCRIPTION'];
    $parsed_props = [];
    foreach($this->props as $name=>$pfields){
      $parsed_props[$name] = [];
      //По какой-то причине для свойства "Привязка к элементам" нельзя передавать ['VALUE'=>...]
      if($pfields['PROPERTY_TYPE'] == 'E'){
        if(array_key_exists('VALUE',$this->props_change[$name]))
          $parsed_props[$name] = $this->props_change[$name]['VALUE'];
        elseif(array_key_exists('VALUE',$pfields))
          $parsed_props[$name] = $pfields['VALUE'];
        continue;
      }
      foreach($props_keys as $key){
        if(array_key_exists($key,$this->props_change[$name]))
          $parsed_props[$name][$key] = $this->props_change[$name][$key];
        elseif(array_key_exists($key,$pfields))
          $parsed_props[$name][$key] = $pfields[$key];
      }
    }
    
    if(!empty($this->fields_change)){
      $toupd = $this->fields_change;
    }else{
      $toupd = [];
    }
    
    $toupd['PROPERTY_VALUES'] = $parsed_props;
    $toupd['_BYAPI'] = $byapi;
    //d($toupd['PROPERTY_VALUES']['tags']);return false;
    $res = (new CIBlockElement)->Update($this->ID, $toupd);
    if($res){
      foreach($this->fields_change as $key=>$val){
        $this->fields[$key] = $val;
      }
      foreach($this->props_change as $code=>$prop){
        foreach($prop as $key=>$val){
          $this->props[$code][$key] = $val;
        }
      }
      $this->has_changes = false;
      $this->fields_change = [];
      $this->props_change = [];
    }
    return $res;
  }

  public function rollback(): void{
    if($this->has_changes){
      $this->has_changes = false;
      $this->fields_change = [];
      $this->props_change = [];
    }
  }
  
  public function delete(): bool{
    return CIBlockElement::Delete($this->getID());
  }
                      
  public static function create(int $IB, array $fields, array $props=null, $return=false, &$error=false){
    if(!CModule::includeModule('iblock'))
      return false;
    $fields['IBLOCK_ID'] = $IB;
    if(!$fields['NAME'])
      $fields['NAME'] = md5(rand());
    if(!$fields['ACTIVE'])
      $fields['ACTIVE'] = 'Y';
    if(!$fields['MODIFIED_BY'])
      $fields['MODIFIED_BY'] = 1;
    if(!$fields['SORT'])
      $fields['SORT'] = 500;
    if(!$fields['CODE'])
      $fields['CODE'] = md5(rand());
    if(!$fields['ACTIVE_FROM'])
      $fields['ACTIVE_FROM'] = (new \Bitrix\Main\Type\DateTime())->toString();
    if(is_array($props))
      $fields['PROPERTY_VALUES'] = $props;

    $fields['_BYAPI'] = true;
    $el = new CIBlockElement;
    if($ID = $el->Add($fields))
       return $return?self::getByID($IB, $ID):$ID;
    $error = $el->LAST_ERROR;
    return false;
  } 
                      
  public static function getByID(int $IB = 0, int $ID){
    if(CModule::includeModule('iblock')){
      $filter = ['ID' => $ID];
      if($IB > 0)
        $filter['IBLOCK_ID'] = $IB;
      $res = CIBlockElement::GetList(["SORT" => "ASC"], $filter);
      if($el = $res->GetNextElement()){
        return new static($el->getFields(),$el->getProperties());
      }
    }
    return false;
  }
  
  public static function getList(int $IB, array $filter=[], array $order=["SORT" => "ASC"]){
    if(CModule::includeModule('iblock')){
      $out = [];
      $filter['IBLOCK_ID'] = $IB;
      $res = CIBlockElement::GetList($order, $filter);
      while($el = $res->GetNextElement()){
        $out[$el->getFields()['ID']] = new static($el->getFields(),$el->getProperties());
      }
      return $out;
    }
    return false;
  }
  
  public static function makeByFields($arFields): self{
    return new static($arFields, $arFields['PROPERTIES']);
  }
                      
  public static function getEnumList(string $code, int $ib = 0): array{
    if(!CModule::includeModule('iblock'))
      return [];
    $out = [];
    $filter = ['CODE' => $code];
    if($ib > 0)
      $filter['IBLOCK_ID'] = $ib;
    if($property_enums = CIBlockPropertyEnum::GetList(["SORT"=>"ASC"], $filter))
      while($enum_fields = $property_enums->GetNext())
        $out[$enum_fields['ID']] = $enum_fields;
    return $out;
  }
  
  public static function getPropsOptions(int $ib = 0, $filter = []): array{
    if(!CModule::includeModule('iblock'))
      return [];
    $out = [];
    if($ib > 0)
      $filter['IBLOCK_ID'] = $ib;
    if($res = CIBlockProperty::GetList(["sort"=>"asc"],$filter))
      while($e = $res->GetNext())
        $out[$e['CODE']] = $e;
    return $out;
  }
  
  public static function elementCopy(int $sourceID, int $distanceIB = 0, $distanceID = 0, &$msg = false, string $external_id = ''): int{
    if(!CModule::includeModule('iblock'))
      return 0;
    $result = 0;
    if(($res = CIBlockElement::GetByID($sourceID)) && ($ob = $res->getNextElement())){

      $arFields = $ob->GetFields();
      $arFields['PROPERTIES'] = $ob->GetProperties();

      $arFieldsCopy = array_filter($arFields, function($key){
        return !startsWith($key,'~');
      },ARRAY_FILTER_USE_KEY);
      if($external_id)
        $arFieldsCopy['EXTERNAL_ID'] = $external_id;
      if($distanceIB > 0)
        $arFieldsCopy['IBLOCK_ID'] = $distanceIB;
      
      if(is_numeric($arFieldsCopy['PREVIEW_PICTURE']))
        $arFieldsCopy['PREVIEW_PICTURE'] = CFile::MakeFileArray($arFieldsCopy['PREVIEW_PICTURE']);
      if(is_numeric($arFieldsCopy['DETAIL_PICTURE']))
        $arFieldsCopy['DETAIL_PICTURE'] = CFile::MakeFileArray($arFieldsCopy['DETAIL_PICTURE']);
      
      $arFieldsCopy['PROPERTY_VALUES'] = array();
      $filestodel = [];
      foreach ($arFields['PROPERTIES'] as $property) {
           $arFieldsCopy['PROPERTY_VALUES'][$property['CODE']] = $property['VALUE'];
           if ($arProp['PROPERTY_TYPE']=='L'){
               if ($arProp['MULTIPLE']=='Y'){
                   $arFieldsCopy['PROPERTY_VALUES'][$arProp['CODE']] = array();
                   foreach($arProp['VALUE_ENUM_ID'] as $enumID){
                       $arFieldsCopy['PROPERTY_VALUES'][$arProp['CODE']][] = array(
                           'VALUE' => $enumID
                       );
                   }
               } else {
                   $arFieldsCopy['PROPERTY_VALUES'][$arProp['CODE']] = array(
                       'VALUE' => $arProp['VALUE_ENUM_ID']
                   );
               }
           }
           if ($property['PROPERTY_TYPE']=='F') {
             $filestodel[] = $property['CODE'];
               if ($property['MULTIPLE']=='Y') {
                   if (is_array($property['VALUE'])){
                       foreach ($property['VALUE'] as $key => $arElEnum) if(is_numeric($arElEnum))
                         $arFieldsCopy['PROPERTY_VALUES'][$property['CODE']][$key] = CFile::MakeFileArray($arElEnum);                          
                   }                
               } else
                   $arFieldsCopy['PROPERTY_VALUES'][$property['CODE']] = ['n0' => CFile::MakeFileArray($property['VALUE'])];
           }
      }
      $el = new CIBlockElement();
      //Очищаем старые файлы
      if($distanceID > 0 && count($filestodel) > 0)
        foreach($filestodel as $ftd)
          if($rsPropOldValues = $el->GetProperty($arFieldsCopy['IBLOCK_ID'], $distanceID, "sort", "asc", array("CODE" => $ftd, "EMPTY" => "N")))
            while($arOldPropertyValue = $rsPropOldValues->Fetch())
              $arFieldsCopy['PROPERTY_VALUES'][$arOldPropertyValue['CODE']][$arOldPropertyValue["PROPERTY_VALUE_ID"]] = ["VALUE" => ["del" => "Y"], "DESCRIPTION" => ""];

      //d($arFieldsCopy);
      $arFieldsCopy['_BYAPI'] = true;
      if($distanceID > 0)
        $result = $el->Update($distanceID, $arFieldsCopy);
      else
        $result = $el->Add($arFieldsCopy);
      $msg = $el->LAST_ERROR;
    }
    return $result;
  }
  
  public static function getIBArrByID($id){
    if(!CModule::includeModule('iblock'))
      return false;
    if(($res = CIBlock::GetByID($id)) && ($ib = $res->GetNext()))
      return $ib;
    return false;
  }
  
  public static function getSitesForIB($id){
    if(!CModule::includeModule('iblock'))
      return false;
    $out = [];
    if($res = CIBlock::GetSite($id))
      while($e = $res->Fetch())
        $out[$e["SITE_ID"]] = $e;
    return $out;
  }
}
