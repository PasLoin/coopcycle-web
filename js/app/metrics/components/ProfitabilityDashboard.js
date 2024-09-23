import React, { useState } from 'react'
import { connect } from 'react-redux'
import { InputNumber, Form } from 'antd';
import _ from 'lodash'

import ProfitabilityHeatmap from './ProfitabilityHeatmap'
import ProfitabilityBars from './ProfitabilityBars'
import ChartPanel from './ChartPanel'

const Dashboard = ({ cubejsApi, dateRange }) => {

  const [ fixedCosts, setFixedCosts ] = useState(0)

  const setFixedCostsDebounced = _.debounce(setFixedCosts, 300)

  return (
    <div>
      <Form.Item label="Monthly fixed costs">
        <InputNumber defaultValue={ fixedCosts } onChange={ setFixedCostsDebounced } />
      </Form.Item>
      <div className="metrics-grid">
        <ChartPanel title="Profitability Heatmap" className="d-block">
          <ProfitabilityHeatmap cubejsApi={ cubejsApi } dateRange={ dateRange } fixedCosts={ fixedCosts } />
        </ChartPanel>
        <ChartPanel title="Profitability Bar Chart">
          <ProfitabilityBars cubejsApi={ cubejsApi } dateRange={ dateRange } fixedCosts={ fixedCosts } />
        </ChartPanel>
      </div>
    </div>
  )
}

function mapStateToProps(state) {

  return {
    dateRange: state.dateRange,
  }
}

export default connect(mapStateToProps)(Dashboard)
