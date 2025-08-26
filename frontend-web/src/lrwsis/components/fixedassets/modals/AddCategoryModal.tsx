import * as React from 'react'
import { useClassOptions, useCreateCategory } from '@/hooks/useLookups'

type Props = {
  companyId: number
  selectedClassCode?: string
  onClose(): void
}

export default function AddCategoryModal({ companyId, selectedClassCode, onClose }: Props) {
  const { data: classOptions = [] } = useClassOptions(companyId)

  // form state
  const [classCode, setClassCode] = React.useState<string>(selectedClassCode ?? '')
  const [catCode, setCatCode]     = React.useState<string>('')
  const [catName, setCatName]     = React.useState<string>('')
  const [isActive, setIsActive]   = React.useState<boolean>(true)

  // bind the mutation to the CURRENT classCode so it invalidates the right key
  const createCategory = useCreateCategory(companyId, classCode)

  // when class options load and we don't have a selection yet, default to first
  React.useEffect(() => {
    if (!classCode && classOptions.length > 0) {
      setClassCode(classOptions[0].value)
    }
  }, [classOptions, classCode])

  function onSubmitCategory(e: React.FormEvent) {
    e.preventDefault()
    if (!classCode || !catCode || !catName) return

    createCategory.mutate(
      {
        class_code: classCode,
        cat_code: catCode.trim(),
        cat_name: catName.trim(),
        is_active: isActive,
        sort_order: 0,
      },
      { onSuccess: () => onClose() }
    )
  }

  return (
    <form onSubmit={onSubmitCategory} className="p-4 space-y-4 min-w-[520px]">
      {/* Class dropdown */}
      <div className="space-y-1">
        <label className="block text-sm font-medium">Class</label>
        <select
          className="w-full border rounded px-3 py-2"
          value={classCode}
          onChange={(e) => setClassCode(e.target.value)}
        >
          {classOptions.map((opt) => (
            <option key={opt.value} value={opt.value}>
              {opt.value} — {opt.label}
            </option>
          ))}
        </select>
      </div>

      {/* Category code + name */}
      <div className="grid grid-cols-2 gap-3">
        <div>
          <label className="block text-sm font-medium">Category Code</label>
          <input
            className="w-full border rounded px-3 py-2"
            value={catCode}
            onChange={(e) => setCatCode(e.target.value.toUpperCase())}
            placeholder="e.g. CCTV"
            maxLength={25}
          />
        </div>
        <div>
          <label className="block text-sm font-medium">Category Name</label>
          <input
            className="w-full border rounded px-3 py-2"
            value={catName}
            onChange={(e) => setCatName(e.target.value)}
            placeholder="CCTV Cameras & Recording Systems"
            maxLength={150}
          />
        </div>
      </div>

      {/* Active */}
      <label className="inline-flex items-center gap-2">
        <input
          type="checkbox"
          checked={isActive}
          onChange={(e) => setIsActive(e.target.checked)}
        />
        <span>Active</span>
      </label>

      {/* Actions */}
      <div className="flex gap-2 justify-end pt-2">
        <button
          type="button"
          className="px-4 py-2 rounded border"
          onClick={onClose}
        >
          Cancel
        </button>
        <button
          type="submit"
          className="px-4 py-2 rounded text-white bg-green-600 disabled:opacity-50"
          disabled={!classCode || !catCode || !catName || createCategory.isPending}
        >
          {createCategory.isPending ? 'Saving…' : 'Submit'}
        </button>
      </div>
    </form>
  )
}
