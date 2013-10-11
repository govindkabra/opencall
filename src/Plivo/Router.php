<?php

namespace Plivo;

use OnCall\Bundle\AdminBundle\Model\ItemStatus;

class Router
{
    protected $pdo;
    protected $num_data;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->num_data = null;
    }

    public function resolve(Parameters $params)
    {
        $num = $params->getTo();
        $res = $this->checkNumber($num);
        $this->num_data = $res;

        // create resposne
        $resp = new Response();
        $act_params = array();

        // check if it has custom xml
        if ($res['xml_override'])
        {
            $xml = $res['xml_replace'];
            $act_params['xml'] = $xml;
            $action = new Action(Action::TYPE_CUSTOM_XML, $act_params);
            $resp->addAction($action);
        }
        else
        {
            $dest = $res['destination'];
            $act_params['number'] = $dest;
            // set caller as caller_id to redirected call
            if ($params->getFrom() != null)
                $act_params['caller_id'] = $params->getFrom();
            // $act_params['caller_id'] = $params->getTo();
            $action = new Action(Action::TYPE_DIAL, $act_params);
            $resp->addAction($action);
        }

        return $resp;
    }

    public function getNumberData()
    {
        return $this->num_data;
    }

    protected function checkNumber($num)
    {
        $status = ItemStatus::ACTIVE;
        $sql = 'select *,Advert.id as advert_id from Number,Advert,AdGroup,Campaign,Client
            where Number.id = :num_id
            and Advert.number_id = Number.id
            and Advert.adgroup_id = AdGroup.id
            and AdGroup.campaign_id = Campaign.id
            and Campaign.client_id = Client.id
            and Advert.status = :status
            and AdGroup.status = :status
            and Campaign.status = :status
            and Client.status = :status';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':num_id', $num);
        $stmt->bindParam(':status', $status);
        if (!$stmt->execute())
            throw new Exception('Database problem encountered.');

        $row = $stmt->fetch();
        if (!$row)
            return false;

        return $row;
    }
}
