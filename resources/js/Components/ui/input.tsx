import type { ComponentProps } from "react";
import { cva, type VariantProps } from "class-variance-authority";
import { cn } from "./utils";

const inputVariants = cva(
  cn(
    "min-h-11 min-w-0 rounded-md border border-input bg-background px-3 py-1 text-base",
    "selection:bg-primary selection:text-primary-foreground placeholder:text-muted-foreground",
    "focus-visible:outline-2 focus-visible:outline-offset-3 focus-visible:outline-ring",
    "aria-invalid:border-destructive disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50",
    "transition-colors motion-reduce:transition-none",
  ),
  {
    variants: {
      variant: {
        default: "w-full",
        score:
          "h-11 w-[88px] bg-input-surface text-right tabular-nums read-only:border-border read-only:bg-secondary",
        search:
          "w-full border-0 bg-transparent p-0 text-sm focus-visible:outline-offset-0",
        checkbox:
          "w-[18px] shrink-0 rounded-none border-0 bg-transparent p-0 accent-primary",
        choice: "peer absolute h-11 w-full opacity-0 disabled:opacity-0",
      },
    },
    defaultVariants: { variant: "default" },
  },
);

export function Input({
  className,
  type,
  variant,
  ...props
}: ComponentProps<"input"> & VariantProps<typeof inputVariants>) {
  return (
    <input
      type={type}
      data-slot="input"
      className={cn(inputVariants({ variant }), className)}
      {...props}
    />
  );
}
