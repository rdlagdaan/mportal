import { useState } from "react";
import { PlusCircleIcon } from "@heroicons/react/24/outline";
import TriageIntakeModal from "./TriageIntakeModal";

/**
 * Page-level wrapper for Triage & Intake.
 * Always renders when the sidebar route is clicked.
 * Controls modal open/close state.
 */
export default function TriageIntake() {
  const [modalOpen, setModalOpen] = useState(false);

  return (
    <div className="p-6 bg-gray-50 min-h-full faims-ui">
      {/* Toolbar */}
      <div className="mb-3 flex items-center gap-2">
        <button
          onClick={() => setModalOpen(true)}
          className="inline-flex items-center gap-2 bg-green-700 text-white px-4 py-2 rounded hover:bg-green-600"
        >
          <PlusCircleIcon className="h-5 w-5" /> New Encounter
        </button>
      </div>

      {/* Placeholder table – wire this later to /mdo/encounters */}
      <div className="border bg-white rounded-xl shadow-sm faims-table">
        <table className="w-full text-left">
          <thead className="bg-amber-50 text-gray-800">
            <tr>
              <th className="p-2">#</th>
              <th className="p-2">Encounter ID</th>
              <th className="p-2">Person</th>
              <th className="p-2">Provider</th>
              <th className="p-2">Complaint</th>
              <th className="p-2">Status</th>
              <th className="p-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td colSpan={7} className="p-4 text-center text-gray-500">
                No records yet
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      {/* Modal */}
      <TriageIntakeModal
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        defaultCompanyId={1}
      />
    </div>
  );
}
