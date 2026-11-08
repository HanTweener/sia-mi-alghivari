export const pupils = [
  { id: "D-001", name: "Alya Contoh" },
  { id: "D-002", name: "Bagas Contoh" },
  { id: "D-003", name: "Citra Contoh" },
  { id: "D-004", name: "Damar Contoh" },
  { id: "D-005", name: "Elin Contoh" },
  { id: "D-006", name: "Fajar Contoh" },
  { id: "D-007", name: "Gita Contoh" },
  { id: "D-008", name: "Hadi Contoh Nama Panjang untuk Uji Tampilan" },
];

export type Scenario = "normal" | "loading" | "error" | "empty" | "conflict";
export type Screen = "grades" | "attendance" | "progress" | "report";
export type GradeValues = Record<string, string>;
export const initialGrades: GradeValues = {
  "D-001": "85",
  "D-002": "0",
  "D-003": "",
  "D-004": "78,5",
  "D-005": "90",
  "D-006": "",
  "D-007": "82",
  "D-008": "",
};

export function gradeValid(value: string) {
  return (
    value === "" ||
    (/^\d{1,3}([.,]\d{1,2})?$/.test(value) &&
      Number(value.replace(",", ".")) <= 100)
  );
}
