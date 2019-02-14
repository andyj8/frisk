<?php

namespace FriskMigrate\Infrastructure\Slapi;

use Doctrine\DBAL\Connection;
use FriskMigrate\Domain\Customer\Customer;
use FriskMigrate\Domain\Voucher\Repository\VoucherRepository;

class SlapiVoucherRepository implements VoucherRepository
{
    /**
     * @var Connection
     */
    private $slapiDb;

    /**
     * @param Connection $slapiDb
     */
    public function __construct(Connection $slapiDb)
    {
        $this->slapiDb = $slapiDb;
    }

    /**
     * @param Customer $customer
     */
    public function saveFor(Customer $customer)
    {
        $voucher = $customer->getVoucher();

        $sql = "SELECT nextval('voucher_id_seq')";
        $voucherId = $this->slapiDb->query($sql)->fetchColumn();

        $this->slapiDb->insert('voucher', [
            'id'           => $voucherId,
            'criteria_id'  => $this->getCriteriaId(),
            'code'         => $voucher->getCode(),
            'current_uses' => 0
        ]);

        $this->slapiDb->update(
            'person',
            ['nook_refund_amount' => $voucher->getValue()],
            ['id' => $customer->getId()]
        );
    }

    /**
     * @return string
     */
    private function getCriteriaId()
    {
        $criteriaId = $this->slapiDb->createQueryBuilder()
            ->select('c.id')
            ->from('promotion_criteria', 'c')
            ->innerJoin('c', 'promotion', 'p', 'p.id = c.promotion_id')
            ->where("p.family = 'nook'")
            ->execute()
            ->fetchColumn();

        if (!$criteriaId) {
            $promotionId = $this->insertPromotion();
            $this->insertOutcome($promotionId);
            $criteriaId  = $this->insertCriteria($promotionId);
        }

        return $criteriaId;
    }

    /**
     * @return string
     */
    private function insertPromotion()
    {
        $sql = "SELECT nextval('promotion_id_seq')";
        $promotionId = $this->slapiDb->query($sql)->fetchColumn();

        $this->slapiDb->insert('promotion', [
            'id'          => $promotionId,
            'name'        => 'Nook Migration Voucher',
            'start_time'  => '2016-01-01 00:00:00',
            'end_time'    => '2016-09-30 23:59:59',
            'description' => 'Nook voucher',
            'family'      => 'nook'
        ]);

        return $promotionId;
    }

    /**
     * @param $promotionId
     */
    private function insertOutcome($promotionId)
    {
        $sql = "SELECT nextval('promotion_outcome_id_seq')";
        $id = $this->slapiDb->query($sql)->fetchColumn();

        $this->slapiDb->insert('promotion_outcome', [
            'id'           => $id,
            'promotion_id' => $promotionId,
            'message'      => 'Nook Voucher',
            'options'      => '{}',
            'sort_order'   => 1,
            'type'         => 'discount_nook_refund'
        ]);
    }

    /**
     * @param $promotionId
     *
     * @return string
     */
    private function insertCriteria($promotionId)
    {
        $sql = "SELECT nextval('promotion_criteria_id_seq')";
        $criteriaId = $this->slapiDb->query($sql)->fetchColumn();

        $this->slapiDb->insert('promotion_criteria', [
            'id'           => $criteriaId,
            'promotion_id' => $promotionId,
            'options'      => '{}',
            'type'         => 'single_use_voucher_code'
        ]);

        $this->slapiDb->insert('singleusevouchercode', [
            'id'       => $criteriaId,
            'max_uses' => 1,
            'vouchers_amount_to_generate' => 1
        ]);

        return $criteriaId;
    }
}
