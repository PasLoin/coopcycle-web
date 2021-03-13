import React from 'react'
import moment from 'moment'
import { nowToPercentage, timeframeToPercentage } from '../redux/utils'

let listeners = []

const addListener = (cb) => listeners.push(cb)
const removeListener = (cb) => {
  listeners = listeners.filter(listener => listener !== cb)
}

setInterval(() => {
  listeners.forEach(listener => listener())
}, 1000 * 60)

class NowCursor extends React.Component {

  constructor(props) {
    super(props)

    this.state = {
      left: nowToPercentage() * 100,
    }

    this.tick = this.tick.bind(this)
  }

  componentDidMount () {
    addListener(this.tick)
  }

  componentWillUnmount() {
    removeListener(this.tick)
  }

  tick() {
    this.setState({
      left: nowToPercentage() * 100,
    })
  }

  render() {

    return (
      <span className="task__eta__now" style={{ left: `${this.state.left.toFixed(4)}%` }}></span>
    )
  }
}

class TaskEta extends React.Component {

  constructor(props) {
    super(props)

    this.state = {
      timeframeLeft: 0,
      timeframeWidth: 0,
    }
  }

  render() {

    const { after, before } = this.props.task
    const [ percentAfter, percentBefore ] = timeframeToPercentage([ after, before ], moment(this.props.date))
    const isSameDay = moment(this.props.date).format('YYYY-MM-DD') === moment().format('YYYY-MM-DD')
    const timeframeLeft = (percentAfter * 100)
    const timeframeWidth = ((percentBefore - percentAfter) * 100)

    return (
      <span className="task__eta">
        <span className="task__eta__timeframe" style={{ left: `${timeframeLeft.toFixed(4)}%`, width: `${timeframeWidth.toFixed(4)}%` }}></span>
        { isSameDay && <NowCursor /> }
      </span>
    )
  }
}

export default TaskEta
