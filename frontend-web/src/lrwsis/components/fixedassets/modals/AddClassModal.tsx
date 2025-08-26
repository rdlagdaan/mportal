import * as React from 'react'
import { useCreateClass } from '@/hooks/useLookups'

type Props = { companyId: number; onClose(): void }

export default function AddClassModal({ companyId, onClose }: Props) {
  const createClass = useCreateClass(companyId)

  function onSubmitClass(values: any) {
    createClass.mutate(values, { onSuccess: () => onClose() })
  }

  // your form UI here; call onSubmitClass(formValues)
  return null
}
