// useLookups.ts
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';

export function useClassOptions(companyId?: number) {
  return useQuery({
    queryKey: ['lookup', 'classes', companyId ?? 0],
    queryFn: async () => {
      const qs = companyId ? `?company_id=${companyId}` : '';
      const r = await fetch(`/app/api/assets/lookups/classes${qs}`, {
        credentials: 'include',
      });
      if (!r.ok) throw new Error('fetch failed');
      return r.json() as Promise<Array<{ value: string; label: string }>>;
    },
    staleTime: 60_000,
  });
}

export function useCategoryOptions(companyId: number, classCode?: string) {
  return useQuery({
    enabled: !!classCode,
    queryKey: ['lookup','categories', companyId, classCode],
    queryFn: async () => {
      const r = await fetch(`/app/api/assets/lookups/categories?company_id=${companyId}&class_code=${encodeURIComponent(classCode!)}`, { credentials: 'include' });
      if (!r.ok) throw new Error('failed to load categories');
      return r.json() as Promise<Array<{value:string;label:string;class_code:string}>>;
    },
  });
}


export function useCreateClass(companyId: number) {
  const qc = useQueryClient();

  return useMutation({
    // POST /app/api/assets/classes
    mutationFn: async (payload: any) => {
      const r = await fetch('/app/api/assets/classes', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ ...payload, company_id: companyId }),
      });
      if (!r.ok) throw new Error('create failed');
      return r.json(); // expected to contain { class_code, class_name, ... }
    },

    onSuccess: (row) => {
      // 1) Immediately update the dropdown cache so it appears without refresh
      qc.setQueryData(['lookup', 'classes', companyId], (old: any) => {
        const list = Array.isArray(old) ? [...old] : [];
        const i = list.findIndex((o: any) => o.value === row.class_code);
        if (i === -1) {
          list.push({ value: row.class_code, label: row.class_name });
        } else {
          // update label if it changed
          list[i] = { ...list[i], label: row.class_name };
        }
        list.sort((a: any, b: any) => String(a.value).localeCompare(String(b.value)));
        return list;
      });

      // 2) Also invalidate to refetch everywhere else as a safety net
      qc.invalidateQueries({ queryKey: ['lookup', 'classes', companyId] });
    },
  });
}


export function useCreateCategory(companyId: number, classCode: string) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: any) => {
      const r = await fetch('/app/api/assets/categories', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ ...payload, company_id: companyId }),
      });
      if (!r.ok) throw new Error('create failed');
      return r.json();
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['lookup','categories', companyId, classCode] });
    },
  });
}
