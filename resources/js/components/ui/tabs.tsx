import * as React from "react"

import { cn } from "@/lib/utils"

type TabsContextValue = {
  value: string
  onValueChange: (value: string) => void
}

const TabsContext = React.createContext<TabsContextValue | null>(null)

function useTabsContext(): TabsContextValue {
  const context = React.useContext(TabsContext)

  if (!context) {
    throw new Error("Tabs components must be used within a Tabs root")
  }

  return context
}

function Tabs({
  className,
  defaultValue,
  value,
  onValueChange,
  ...props
}: React.ComponentProps<"div"> & {
  defaultValue?: string
  value?: string
  onValueChange?: (value: string) => void
}) {
  const [internalValue, setInternalValue] = React.useState(defaultValue ?? "")

  const currentValue = value ?? internalValue

  const contextValue = React.useMemo(
    () => ({
      value: currentValue,
      onValueChange: (nextValue: string) => {
        setInternalValue(nextValue)
        onValueChange?.(nextValue)
      },
    }),
    [currentValue, onValueChange]
  )

  return (
    <TabsContext.Provider value={contextValue}>
      <div
        data-slot="tabs"
        className={cn("flex flex-col gap-2", className)}
        {...props}
      />
    </TabsContext.Provider>
  )
}

function TabsList({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="tabs-list"
      role="tablist"
      className={cn(
        "bg-muted text-muted-foreground inline-flex h-9 w-fit items-center justify-center rounded-lg p-[3px]",
        className
      )}
      {...props}
    />
  )
}

function TabsTrigger({ className, value, ...props }: React.ComponentProps<"button"> & { value: string }) {
  const { value: selectedValue, onValueChange } = useTabsContext()

  const selected = selectedValue === value

  return (
    <button
      type="button"
      role="tab"
      data-slot="tabs-trigger"
      aria-selected={selected}
      data-state={selected ? "active" : "inactive"}
      tabIndex={selected ? 0 : -1}
      onClick={() => onValueChange(value)}
      className={cn(
        "data-[state=active]:bg-background data-[state=active]:text-foreground focus-visible:ring-ring/50 focus-visible:border-ring inline-flex flex-1 items-center justify-center gap-1.5 rounded-md px-2 py-1 text-sm font-medium whitespace-nowrap transition-[color,box-shadow] focus-visible:ring-[3px] focus-visible:outline-1 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:shadow-sm [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4",
        className
      )}
      {...props}
    />
  )
}

function TabsContent({
  className,
  value,
  ...props
}: React.ComponentProps<"div"> & { value: string }) {
  const { value: selectedValue } = useTabsContext()

  if (selectedValue !== value) {
    return null
  }

  return (
    <div
      data-slot="tabs-content"
      data-state={selectedValue === value ? "active" : "inactive"}
      role="tabpanel"
      tabIndex={0}
      className={cn("flex-1 outline-none", className)}
      {...props}
    />
  )
}

export { Tabs, TabsList, TabsTrigger, TabsContent }
