import { useRef, type ReactNode } from "react";
import {
  Dialog as DialogRoot,
  DialogContent,
  DialogDescription,
  DialogTitle,
} from "./dialog.registry";

// Local shadcn/ui pattern: Radix supplies focus trapping, Escape and restoration.
export function Dialog({
  open,
  onOpenChange,
  title,
  description,
  children,
  onReturnFocus,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  title: string;
  description: string;
  children: ReactNode;
  onReturnFocus?: (opener: HTMLElement | null) => void;
}) {
  const opener = useRef<HTMLElement | null>(null);
  return (
    <DialogRoot open={open} onOpenChange={onOpenChange}>
      <DialogContent
        className="max-h-[calc(100dvh-32px)] overflow-y-auto"
        onOpenAutoFocus={() => {
          opener.current = document.activeElement as HTMLElement;
        }}
        onCloseAutoFocus={(event) => {
          event.preventDefault();
          if (onReturnFocus) onReturnFocus(opener.current);
          else if (opener.current?.isConnected) opener.current.focus();
        }}
      >
        <DialogTitle className="pr-10 text-xl font-semibold">
          {title}
        </DialogTitle>
        <DialogDescription className="mt-2 text-sm leading-6 text-muted-foreground">
          {description}
        </DialogDescription>
        {children}
      </DialogContent>
    </DialogRoot>
  );
}
