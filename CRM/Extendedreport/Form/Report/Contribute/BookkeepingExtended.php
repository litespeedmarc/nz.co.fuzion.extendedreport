<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 */
class CRM_Extendedreport_Form_Report_Contribute_BookkeepingExtended extends CRM_Extendedreport_Form_Report_ExtendedReport {
  /**
   * Class constructor.
   */
  public function __construct() {
    $this->_columns = $this->getColumns('Contact')
    + $this->getColumns('Address')
    + $this->getColumns('Phone', array('subquery' => FALSE,))
    + $this->getColumns('Email')
    + $this->getColumns('Membership')
    + $this->getColumns('FinancialAccount', array(
        'prefix' => 'credit_',
        'group_by' => TRUE,
        'prefix_label' => ts('Credit '),
        'filters' => TRUE,
      ))
    + $this->getColumns('FinancialAccount', array(
      'prefix' => 'debit_',
      'group_by' => TRUE,
      'prefix_label' => ts('Debit '),
      'filters' => FALSE,
    ))
    + $this->getColumns('LineItem')
    + $this->getColumns('Contribution', array(
      'field_defaults' => array('receive_date', 'id'),
      'filters_defaults' => array('contribution_status_id' => array('IN' => array(1)),
     )))
    + array(
      'civicrm_financial_trxn' => array(
        'dao' => 'CRM_Financial_DAO_FinancialTrxn',
        'fields' => array(
          'check_number' => array(
            'title' => ts('Cheque #'),
            'default' => TRUE,
          ),
          'payment_instrument_id' => array(
            'title' => ts('Payment Instrument'),
            'default' => TRUE,
            'alter_display' => 'alterPaymentType',
          ),
          'currency' => array(
            'required' => TRUE,
            'no_display' => TRUE,
          ),
          'trxn_date' => array(
            'title' => ts('Transaction Date'),
            'default' => TRUE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'trxn_id' => array(
            'title' => ts('Trans #'),
            'default' => TRUE,
          ),
        ),
        'filters' => array(
          'payment_instrument_id' => array(
            'title' => ts('Payment Instrument'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::paymentInstrument(),
            'type' => CRM_Utils_Type::T_INT,
          ),
          'currency' => array(
            'title' => 'Currency',
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'trxn_date' => array(
            'title' => ts('Transaction Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
        ),
        'order_bys' => array(
          'payment_instrument_id' => array('title' => ts('Payment Instrument')),
        ),
      ),
    ) + array(
      'civicrm_entity_financial_trxn' => array(
        'dao' => 'CRM_Financial_DAO_EntityFinancialTrxn',
        'fields' => array(
          'amount' => array(
            'title' => ts('Amount'),
            'default' => TRUE,
            'type' => CRM_Utils_Type::T_STRING,
            'statistics' => array('sum'),
          ),
        ),
        'filters' => array(
          'amount' => array(
            'title' => ts('Amount'),
            'type' => CRM_Utils_Type::T_MONEY,
          ),
        ),
      ),
    ) + $this->getColumns('Batch', array(
          'group_by' => TRUE,
          'prefix_label' => ts('Batch '),
          'filters' => TRUE,
        )
    )
    ;

    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;
    parent::__construct();
  }

  /**
   * Here we can define select clauses for any particular row.
   *
   * @param string $tableName
   * @param string $tableKey
   * @param string $fieldName
   * @param array $field
   *
   * @return bool|string
   */
  function selectClause(&$tableName, $tableKey, &$fieldName, &$field) {
    $alias = "{$tableName}_{$fieldName}";
    if ($fieldName == 'credit_financial_account_accounting_code') {
      $this->setHeaders($tableName, $fieldName, $field, $alias);
      return "
        CASE
        WHEN {$this->_aliases['civicrm_financial_trxn']}.from_financial_account_id IS NOT NULL
        THEN {$this->_aliases['credit_civicrm_financial_account']}.accounting_code
        ELSE credit_financial_item_financial_account.accounting_code
        END AS $alias ";
    }

    if ($fieldName == 'credit_financial_account_name') {
      $this->setHeaders($tableName, $fieldName, $field, $alias);
      return "
        CASE
        WHEN {$this->_aliases['civicrm_financial_trxn']}.from_financial_account_id IS NOT NULL
        THEN {$this->_aliases['credit_civicrm_financial_account']}.name
        ELSE credit_financial_item_financial_account.name
        END AS $alias ";
    }

    if ($fieldName == 'debit_financial_account_accounting_code') {
      $this->setHeaders($tableName, $fieldName, $field, $alias);
      return "
        CASE
        WHEN {$this->_aliases['civicrm_financial_trxn']}.from_financial_account_id IS NOT NULL
        THEN  {$this->_aliases['debit_civicrm_financial_account']}.accounting_code
        ELSE  {$this->_aliases['debit_civicrm_financial_account']}.accounting_code
        END AS $alias ";
    }


    if ($fieldName == 'debit_financial_account_name') {
      $this->setHeaders($tableName, $fieldName, $field, $alias);
      return "
        CASE
        WHEN {$this->_aliases['civicrm_financial_trxn']}.from_financial_account_id IS NOT NULL
        THEN  {$this->_aliases['debit_civicrm_financial_account']}.name
        ELSE  {$this->_aliases['debit_civicrm_financial_account']}.name
        END AS $alias ";
    }

    if ($fieldName == 'amount') {
      $field['dbAlias'] =
      $this->setHeaders($tableName, $fieldName, $field, $alias);
      $clause = "(
        CASE
        WHEN  {$this->_aliases['civicrm_entity_financial_trxn']}_item.entity_id IS NOT NULL
        THEN {$this->_aliases['civicrm_entity_financial_trxn']}_item.amount
        ELSE {$this->_aliases['civicrm_entity_financial_trxn']}.amount
        END) AS civicrm_entity_financial_trxn_amount ";
      if (!empty($this->_groupByArray) || $this->isForceGroupBy) {
        return " SUM{$clause}";
      }
      return $clause;
    }

    return parent::selectClause($tableName, $tableKey, $fieldName, $field);
  }

  function from() {
    $this->_from = NULL;

    // help make this sql a bit more readable!
    $contact = $this->_aliases['civicrm_contact'];
    $financial_trxn = $this->_aliases['civicrm_financial_trxn'];
    $debit_financial_account = $this->_aliases['debit_civicrm_financial_account'];
    $credit_financial_account = $this->_aliases['credit_civicrm_financial_account'];
    $entity_financial_trxn = $this->_aliases['civicrm_entity_financial_trxn'];
    $line_item= $this->_aliases['civicrm_line_item'];
    $contribution = $this->_aliases['civicrm_contribution'];
    $membership = $this->_aliases['civicrm_membership'];
    $address = $this->_aliases['civicrm_address'];
    $phone = $this->_aliases['civicrm_phone'];
    $email = $this->_aliases['civicrm_email'];

    $this->_from = "FROM civicrm_contact $contact {$this->_aclFrom}
              INNER JOIN civicrm_contribution $contribution
                      ON $contact.id = $contribution.contact_id AND
                         $contribution.is_test = 0
              LEFT  JOIN civicrm_entity_financial_trxn $entity_financial_trxn
                      ON $contribution.id = $entity_financial_trxn.entity_id 
                     AND $entity_financial_trxn.entity_table = 'civicrm_contribution'
              LEFT  JOIN civicrm_financial_trxn $financial_trxn
                      ON $financial_trxn.id = $entity_financial_trxn.financial_trxn_id
              LEFT  JOIN civicrm_financial_account $debit_financial_account
                      ON $financial_trxn.to_financial_account_id = $debit_financial_account.id
              LEFT  JOIN civicrm_financial_account $credit_financial_account
                      ON $financial_trxn.from_financial_account_id = $credit_financial_account.id
              LEFT  JOIN civicrm_entity_financial_trxn {$entity_financial_trxn}_item
                      ON $financial_trxn.id = {$entity_financial_trxn}_item.financial_trxn_id 
                     AND {$entity_financial_trxn}_item.entity_table = 'civicrm_financial_item'
              ";

    if (!empty($this->_aliases['civicrm_batch'])) {
      $batch = $this->_aliases['civicrm_batch'];
      $this->_from .= "
              -- to get the batch name, if contribution has been assigned to a batch.
              LEFT  JOIN civicrm_entity_batch entity_batch
                      ON entity_batch.entity_id = $financial_trxn.id 
                     AND entity_batch.entity_table = 'civicrm_financial_trxn'
              LEFT  JOIN civicrm_batch $batch
                      ON $batch.id = entity_batch.batch_id
                      ";
    }

    $this->_from .= "
              -- to get the line items (e.g., line item financial type)
              LEFT  JOIN civicrm_financial_item fitem
                      ON fitem.id = {$entity_financial_trxn}_item.entity_id
              LEFT  JOIN civicrm_financial_account credit_financial_item_financial_account
                      ON fitem.financial_account_id = credit_financial_item_financial_account.id
                      
              -- to get the membership
              LEFT  JOIN civicrm_line_item $line_item
                      ON fitem.entity_id = $line_item.id AND fitem.entity_table = 'civicrm_line_item'
              LEFT  JOIN civicrm_line_item mem_line_item 
                      ON mem_line_item.price_field_id = $line_item.price_field_id 
                     AND mem_line_item.contribution_id = contribution.id 
                     AND mem_line_item.entity_Table = 'civicrm_membership'
                     
              -- Next join just in case contribution does not have a price set associated to it.
              -- Typically, anything with a membership *would* get a price set (either user configured
              -- or a quick config one).  But for historical or unconfirmed 'hear-say' bugs reasons,
              -- this may or may not.  Keeping this historical 'more dangerous join' just in case.
              LEFT  JOIN civicrm_membership_payment payment
                      ON $contribution.id = payment.contribution_id 
                  	 AND $line_item.price_field_id IS NULL -- IS NULL because if there is a price set
                  	                                       -- Then this join shouldn't be used.
                  	                                      
              -- The membership join.  Ideally, just on mem_line_item, but for 'just-in-case' reasons,
              -- Also keep legacy join on payment.membership_id, if mem_line_item join fails.
              LEFT  JOIN civicrm_membership $membership
                      ON $membership.id = COALESCE(mem_line_item.entity_id, payment.membership_id)
                      
              LEFT  JOIN civicrm_address $address 
                      ON $address.contact_id = $contact.id 
                     AND $address.is_primary = 1
              LEFT  JOIN civicrm_phone $phone 
                      ON $phone.contact_id = $contact.id 
                     AND $phone.is_primary = 1
              LEFT  JOIN civicrm_email $email 
                      ON $email.contact_id = $contact.id 
                     AND $email.is_primary = 1
                     AND $email.on_hold = 0
                   ";
  }

  function orderBy() {
    parent::orderBy();

    // please note this will just add the order-by columns to select query, and not display in column-headers.
    // This is a solution to not throw fatal errors when there is a column in order-by, not present in select/display columns.
    foreach ($this->_orderByFields as $orderBy) {
      if (!array_key_exists($orderBy['name'], $this->_params['fields']) &&
        empty($orderBy['section'])
      ) {
        $this->_select .= ", {$orderBy['dbAlias']} as {$orderBy['tplField']}";
      }
    }
  }

  /**
   * Generate where clause.
   *
   * This can be overridden in reports for special treatment of a field
   *
   * @param array $field Field specifications
   * @param string $op Query operator (not an exact match to sql)
   * @param mixed $value
   * @param float $min
   * @param float $max
   *
   * @return null|string
   */
  public function whereClause(&$field, $op, $value, $min, $max) {
    if ($field['dbAlias'] == "{$this->_aliases['credit_civicrm_financial_account']}.accounting_code") {
      $field['dbAlias'] = "CASE
              WHEN financial_trxn_civireport.from_financial_account_id IS NOT NULL
              THEN  {$this->_aliases['credit_civicrm_financial_account']}.accounting_code
              ELSE  credit_financial_item_financial_account.accounting_code
              END";
    }
    if ($field['dbAlias'] == 'credit_financial_account.name') {
      $field['dbAlias'] =  "CASE
              WHEN financial_trxn_civireport.from_financial_account_id IS NOT NULL
              THEN {$this->_aliases['credit_civicrm_financial_account']}.id
              ELSE  credit_financial_item_financial_account.id
              END";

    }
    return parent::whereClause($field, $op, $value, $min, $max);
  }

/*
          if ($fieldName == 'credit_accounting_code') {
            $field['dbAlias'] =
          }
          else if ($fieldName == 'credit_name') {

          }*/

  /**
   * @param $rows
   *
   * @return array
   */
  function statistics(&$rows) {
    $statistics = parent::statistics($rows);

    $select = " SELECT COUNT({$this->_aliases['civicrm_financial_trxn']}.id ) as count,
                {$this->_aliases['civicrm_contribution']}.currency,
                SUM(CASE
                  WHEN {$this->_aliases['civicrm_entity_financial_trxn']}_item.entity_id IS NOT NULL
                  THEN {$this->_aliases['civicrm_entity_financial_trxn']}_item.amount
                  ELSE {$this->_aliases['civicrm_entity_financial_trxn']}.amount
                END) as amount
";

    $sql = "{$select} {$this->_from} {$this->_where}
            GROUP BY {$this->_aliases['civicrm_contribution']}.currency
";

    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $amount[] = CRM_Utils_Money::format($dao->amount, $dao->currency);
      $avg[] = CRM_Utils_Money::format(round(($dao->amount /
        $dao->count), 2), $dao->currency);
    }
    if (empty($amount)) {
      return  $statistics;
    }
    $statistics['counts']['amount'] = array(
      'value' => implode(', ', $amount),
      'title' => 'Total Amount',
      'type' => CRM_Utils_Type::T_STRING,
    );
    $statistics['counts']['avg'] = array(
      'value' => implode(', ', $avg),
      'title' => 'Average',
      'type' => CRM_Utils_Type::T_STRING,
    );
    return $statistics;
  }

  /**
   * @param $rows
   */
  function alterDisplay(&$rows) {
    $contributionTypes = CRM_Contribute_PseudoConstant::financialType();
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus();
    foreach ($rows as $rowNum => $row) {
      // convert display name to links
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        !empty($rows[$rowNum]['civicrm_contact_sort_name']) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url('civicrm/contact/view',
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts('View Contact Summary for this Contact.');
      }

      // handle contribution status id
      if ($value = CRM_Utils_Array::value('civicrm_contribution_contribution_status_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_contribution_status_id'] = $contributionStatus[$value];
      }

      // handle financial type id
      if ($value = CRM_Utils_Array::value('civicrm_line_item_financial_type_id', $row)) {
        $rows[$rowNum]['civicrm_line_item_financial_type_id'] = $contributionTypes[$value];
      }
      if ($value = CRM_Utils_Array::value('civicrm_entity_financial_trxn_amount', $row)) {
        $rows[$rowNum]['civicrm_entity_financial_trxn_amount'] = CRM_Utils_Money::format($rows[$rowNum]['civicrm_entity_financial_trxn_amount'], $rows[$rowNum]['civicrm_financial_trxn_currency']);
      }
    }
    parent::alterDisplay($rows);
  }

  /**
   * @param $tableName
   * @param $fieldName
   * @param $field
   * @param $alias
   */
  protected function setHeaders(&$tableName, &$fieldName, &$field, $alias) {
    $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
    $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
    $this->_columnHeaders["{$tableName}_{$fieldName}"]['dbAlias'] = CRM_Utils_Array::value('dbAlias', $field);
    $this->_selectAliases[] = $alias;
  }

  /**
   * @return int
   */
  public function getBatchColumns() {
    $cnt = CRM_Batch_BAO_Batch::singleValueQuery("SELECT COUNT(*) FROM civicrm_batch");
    if ($cnt == 0) {
      return array();
    }
    $specs = array(
      'name' => array(
        'title' => ts('Batch Name'),
        'is_filters' => TRUE,
        'is_order_bys' => TRUE,
        'is_fields' => TRUE,
        'is_group_bys' => TRUE,
        // keep free form text... there could be lots of batches after a while
        // make selection unwieldy.
        'type' => CRM_Utils_Type::T_STRING,
      ),
      'status_id' => array(
        'title' => ts('Batch Status'),
        'is_filters' => TRUE,
        'is_order_bys' => FALSE,
        'is_fields' => TRUE,
        'is_group_bys' => FALSE,
        // keep free form text... there could be lots of batches after a while
        // make selection unwieldy.
        'alter_display' => 'alterBatchStatus',
        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
        'options' => CRM_Batch_BAO_Batch::buildOptions("status_id"),
        'type' => CRM_Utils_Type::T_INT,
      ),
    );
    return $this->buildColumns($specs, 'civicrm_batch', 'CRM_Batch_DAO_Batch');
  }

  /**
   * @param $value
   *
   * @return mixed
   */
  function alterBatchStatus($value) {
    if (!$value) {
      return ts("N/A");
    }
    $values = CRM_Batch_BAO_Batch::buildOptions('status_id');
    return $values[$value];
  }
}

