import { useEffect, useRef } from 'react';

import { cn } from '@/lib/utils';

interface GlobeProps {
    className?: string;
}

interface Vec3 {
    x: number;
    y: number;
    z: number;
}

const POINT_COUNT = 650;
const LAT_RINGS = [-60, -40, -20, 0, 20, 40, 60];
const LON_RINGS = [0, 30, 60, 90, 120, 150];
const REDUCED_MOTION =
    typeof window !== 'undefined' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function fibonacciSphere(count: number, radius: number): Vec3[] {
    const points: Vec3[] = [];
    const golden = Math.PI * (3 - Math.sqrt(5));

    for (let i = 0; i < count; i++) {
        const y = 1 - (i / (count - 1)) * 2;
        const radiusAtY = Math.sqrt(1 - y * y);
        const theta = golden * i;

        points.push({
            x: Math.cos(theta) * radiusAtY * radius,
            y: y * radius,
            z: Math.sin(theta) * radiusAtY * radius,
        });
    }

    return points;
}

function rotatePoint(p: Vec3, rotX: number, rotY: number): Vec3 {
    const cosY = Math.cos(rotY);
    const sinY = Math.sin(rotY);
    const x = p.x * cosY + p.z * sinY;
    const z = -p.x * sinY + p.z * cosY;

    const cosX = Math.cos(rotX);
    const sinX = Math.sin(rotX);
    const y = p.y * cosX - z * sinX;
    const z2 = p.y * sinX + z * cosX;

    return { x, y, z: z2 };
}

function oklchToRgb(oklch: string): { r: number; g: number; b: number } | null {
    const match = oklch.match(
        /^oklch\(([\d.]+)\s+([\d.]+)\s+([\d.]+)(?:\s*\/\s*[\d.]+)?\)$/,
    );

    if (!match) {
        return null;
    }

    const l = Number(match[1]);
    const c = Number(match[2]);
    const h = (Number(match[3]) * Math.PI) / 180;

    const a = c * Math.cos(h);
    const b = c * Math.sin(h);
    const lPlus = l + 0.3963377774 * a + 0.2158037573 * b;
    const mPlus = l - 0.1055613458 * a - 0.0638541728 * b;
    const sPlus = l - 0.0894841775 * a - 1.291485548 * b;

    const l3 = lPlus ** 3;
    const m3 = mPlus ** 3;
    const s3 = sPlus ** 3;

    const lr = 4.0767416621 * l3 - 3.3077115913 * m3 + 0.2309699292 * s3;
    const lg = -1.2684380046 * l3 + 2.6097574011 * m3 - 0.3413193965 * s3;
    const lb = -0.0041960863 * l3 - 0.7034186147 * m3 + 1.707614701 * s3;

    const to255 = (v: number) =>
        Math.round(Math.max(0, Math.min(1, v)) * 255);

    return { r: to255(lr), g: to255(lg), b: to255(lb) };
}

export function Globe({ className }: GlobeProps) {
    const canvasRef = useRef<HTMLCanvasElement>(null);

    useEffect(() => {
        const canvas = canvasRef.current;
        if (!canvas) {
            return;
        }

        const ctx = canvas.getContext('2d');
        if (!ctx) {
            return;
        }

        const points = fibonacciSphere(POINT_COUNT, 1);
        const ringSegments: Vec3[][] = [];

        LAT_RINGS.forEach((lat) => {
            const phi = (lat * Math.PI) / 180;
            const y = Math.sin(phi);
            const r = Math.cos(phi);
            const ring: Vec3[] = [];
            for (let i = 0; i <= 64; i++) {
                const theta = (i / 64) * Math.PI * 2;
                ring.push({ x: Math.cos(theta) * r, y, z: Math.sin(theta) * r });
            }
            ringSegments.push(ring);
        });

        LON_RINGS.forEach((lon) => {
            const theta = (lon * Math.PI) / 180;
            const ring: Vec3[] = [];
            for (let i = 0; i <= 64; i++) {
                const phi = (i / 64) * Math.PI * 2;
                ring.push({
                    x: Math.cos(phi) * Math.cos(theta),
                    y: Math.sin(phi),
                    z: Math.cos(phi) * Math.sin(theta),
                });
            }
            ringSegments.push(ring);
        });

        let width = 0;
        let height = 0;
        let rotationY = 0;
        let rotationX = -0.35;
        const AUTO_ROTATE_SPEED = 0.12;
        let velocity = AUTO_ROTATE_SPEED;
        let dragging = false;
        let lastPointerX = 0;
        let lastPointerT = 0;
        let rafId = 0;
        let radius = 0;
        const dpr = Math.min(window.devicePixelRatio || 1, 2);

        const getPalette = () => {
            const styles = getComputedStyle(document.documentElement);
            const primary = styles.getPropertyValue('--primary').trim();
            const foreground = styles.getPropertyValue('--foreground').trim();

            const rgb =
                oklchToRgb(primary) ??
                oklchToRgb(foreground) ??
                { r: 214, g: 51, b: 108 };

            return `rgb(${rgb.r}, ${rgb.g}, ${rgb.b})`;
        };

        let palette = getPalette();

        const themeObserver = new MutationObserver(() => {
            palette = getPalette();
        });
        themeObserver.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class'],
        });

        const resize = () => {
            const rect = canvas.getBoundingClientRect();
            width = rect.width;
            height = rect.height;
            radius = Math.min(width, height) * 0.42;

            canvas.width = Math.max(1, Math.floor(width * dpr));
            canvas.height = Math.max(1, Math.floor(height * dpr));
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        };

        const resizeObserver = new ResizeObserver(resize);
        resizeObserver.observe(canvas);
        resize();

        const project = (p: Vec3, rotX: number, rotY: number) => {
            const rotated = rotatePoint(p, rotX, rotY);
            const scale = 1 / (1.25 - rotated.z * 0.25);

            return {
                x: width / 2 + rotated.x * radius * scale,
                y: height / 2 + rotated.y * radius * scale,
                z: rotated.z,
                scale,
            };
        };

        const draw = () => {
            ctx.clearRect(0, 0, width, height);

            const front: Array<{
                x: number;
                y: number;
                z: number;
                scale: number;
            }> = [];
            const back: Array<{
                x: number;
                y: number;
                z: number;
                scale: number;
            }> = [];

            points.forEach((p) => {
                const projected = project(p, rotationX, rotationY);
                (projected.z >= 0 ? front : back).push(projected);
            });

            ctx.lineWidth = 1;
            ringSegments.forEach((ring) => {
                const projected = ring.map((p) =>
                    project(p, rotationX, rotationY),
                );

                for (let i = 0; i < projected.length - 1; i++) {
                    const a = projected[i];
                    const b = projected[i + 1];
                    const isFront = a.z >= 0 || b.z >= 0;

                    ctx.strokeStyle = palette;
                    ctx.globalAlpha = isFront ? 0.1 : 0.035;
                    ctx.beginPath();
                    ctx.moveTo(a.x, a.y);
                    ctx.lineTo(b.x, b.y);
                    ctx.stroke();
                }
            });

            back.forEach((p) => {
                ctx.fillStyle = palette;
                ctx.globalAlpha = 0.12;
                ctx.beginPath();
                ctx.arc(p.x, p.y, 1.1 * p.scale, 0, Math.PI * 2);
                ctx.fill();
            });

            front.forEach((p) => {
                ctx.fillStyle = palette;
                ctx.globalAlpha = 0.85;
                ctx.beginPath();
                ctx.arc(p.x, p.y, 1.8 * p.scale, 0, Math.PI * 2);
                ctx.fill();
            });

            ctx.globalAlpha = 1;
        };

        const loop = () => {
            if (!dragging) {
                rotationY += velocity;
                if (Math.abs(velocity) > AUTO_ROTATE_SPEED) {
                    velocity *= 0.97;
                    if (Math.abs(velocity) <= AUTO_ROTATE_SPEED) {
                        velocity = AUTO_ROTATE_SPEED;
                    }
                }
            }

            draw();
            rafId = requestAnimationFrame(loop);
        };

        const onPointerDown = (event: PointerEvent) => {
            dragging = true;
            lastPointerX = event.clientX;
            lastPointerT = performance.now();
            velocity = 0;
            canvas.setPointerCapture(event.pointerId);
        };

        const onPointerMove = (event: PointerEvent) => {
            if (!dragging) {
                return;
            }

            const deltaX = event.clientX - lastPointerX;
            const now = performance.now();
            const dt = Math.max(1, now - lastPointerT);

            rotationY += deltaX * 0.005;
            velocity = Math.max(
                -0.6,
                Math.min(0.6, (deltaX * 0.005 * 1000) / dt),
            );
            lastPointerX = event.clientX;
            lastPointerT = now;
        };

        const onPointerUp = (event: PointerEvent) => {
            dragging = false;
            canvas.releasePointerCapture(event.pointerId);
        };

        const onPointerLeave = () => {
            dragging = false;
        };

        if (!REDUCED_MOTION) {
            canvas.addEventListener('pointerdown', onPointerDown);
            canvas.addEventListener('pointermove', onPointerMove);
            canvas.addEventListener('pointerup', onPointerUp);
            canvas.addEventListener('pointercancel', onPointerUp);
            canvas.addEventListener('pointerleave', onPointerLeave);

            rafId = requestAnimationFrame(loop);
        } else {
            draw();
        }

        return () => {
            cancelAnimationFrame(rafId);
            themeObserver.disconnect();
            resizeObserver.disconnect();
            canvas.removeEventListener('pointerdown', onPointerDown);
            canvas.removeEventListener('pointermove', onPointerMove);
            canvas.removeEventListener('pointerup', onPointerUp);
            canvas.removeEventListener('pointercancel', onPointerUp);
            canvas.removeEventListener('pointerleave', onPointerLeave);
        };
    }, []);

    return (
        <canvas
            ref={canvasRef}
            aria-label="Interactive rotating globe"
            className={cn(
                'size-full cursor-grab touch-none select-none active:cursor-grabbing',
                className,
            )}
        />
    );
}
