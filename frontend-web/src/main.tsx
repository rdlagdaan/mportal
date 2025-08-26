// src/main.tsx
import React from 'react'
import ReactDOM from 'react-dom/client'
import RootRouter from './root-router'
import './index.css'

ReactDOM.createRoot(document.getElementById('root')!).render(
  <React.StrictMode>
    <RootRouter />
  </React.StrictMode>
)
