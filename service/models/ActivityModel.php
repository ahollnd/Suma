<?php 

class ActivityModel
{
    private $_db;
    private $_metadata;
    private $_id;

    public function __construct($id)
    {
        $this->_db = Globals::getDBConn();
        $select = $this->_db->select()
            ->from('activity')
            ->where('id = '.$id);
        $row = $select->query()->fetch();

        if (empty($row))
        {
            throw new Exception('Object not found in database with id ' . $id);
        }

        foreach ($row as $key => $value)
        {
            $this->_metadata[$key] = $value;
        }
        
        $this->_id = $id;
    }

    public function getMetadata($key = null)
    {
        if (empty($this->_metadata))
        {
            $select = $this->_db->select()
                ->from('activity')
                ->where('id = '.$this->_id);
            $row = $select->query()->fetch();
            
            foreach ($row as $index => $val)
            {
                $this->_metadata[$index] = $val;
            }
        }
        
        if ($key == null)
        {
            return $this->_metadata;
        }
        else 
        {
            if (isset($this->_metadata[$key]))
            {
                return $this->_metadata[$key];
            }
            else
            {
                return '';
            }
        }
    }

    public function update($data)
    {
        $hash = array('title'       =>  $data['title'],
                      'description' =>  $data['desc'],
                      'rank'        =>  $data['rank']);

        if (isset($data['enabled']) && is_bool($data['enabled']))
        {
            $hash['enabled'] = $data['enabled'];
        }

        $this->_db->update('activity', $hash, 'id = '.$this->_id);
        $this->jettisonMetadata();
    }

    public function enable()
    {
        $data = array('enabled'  =>  true);
        $this->_db->update('activity', $data, 'id = '.$this->_id);
        $this->jettisonMetadata();
    }

    public function disable()
    {
        $data = array('enabled'  =>  false);
        $this->_db->update('activity', $data, 'id = '.$this->_id);
        $this->jettisonMetadata();
    }
    
    
    // ------ PRIVATE FUNCTIONS ------        
    
    private function jettisonMetadata()
    {
        $this->_metadata = null;
    }

    
    // ------ STATIC FUNCTIONS ------
    
    public static function create($data)
    {
        $db = Globals::getDBConn();

        $select = $db->select()
            ->from('activity')
            ->where('fk_initiative = '.$data['init'].' AND LOWER(title) = '.$db->quote(strtolower($data['title']))); 
        $existingActivity = $select->query()->fetch();

        if (empty($existingActivity))
        {
            $hash =     array('title'         =>  $data['title'],
                              'enabled'       =>  isset($data['enabled']) ? $data['enabled'] : false,
                              'fk_initiative' =>  $data['init'],
                              'description'   =>  isset($data['desc']) ? $data['desc'] : null,
                              'rank'          =>  isset($data['rank']) ? $data['rank'] : null);

            $db->insert('activity', $hash);            
            return $db->lastInsertId();
        }
    }    

    public static function updateActivitiesArray($activities, $initID=null)
    {
        if (!$initID || !$activities || !is_array($activities) || !is_numeric($initID)) {
            return false;
        }

        foreach($activities as $actKey=>$activity)
        {
            if (!empty($activity['title']) && isset($activity['id'])
                && isset($activity['desc'])
                && (isset($activity['enabled']) && is_bool($activity['enabled'])))
            {
                $actData = Array('id' => $activity['id'],
                    'title' => $activity['title'],
                    'desc' => $activity['desc'],
                    'enabled' => $activity['enabled'],
                    'rank' => $actKey);

                if (is_numeric($actData['id']))
                {
                    $activityObj = new ActivityModel($actData['id']);
                    if (!$activityObj) {
                        return false;
                    }
                    $activityObj->update($actData);
                } elseif ('new-act' === $actData['id'])
                {
                    $actData['init'] = $initID;
                    $activityID = self::create($actData);
                    if (false === $activityID) {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
        return true;
    }
    
}
